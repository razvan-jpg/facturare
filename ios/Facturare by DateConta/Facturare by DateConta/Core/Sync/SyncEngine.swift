import Foundation
import Observation
import SwiftData

enum SyncStatus: Equatable {
    case idle
    case syncing
    case offline
    case error(String)
    case ok

    var label: String {
        switch self {
        case .idle: return "Sincronizat"
        case .syncing: return "Sincronizare…"
        case .offline: return "Offline"
        case .error(let m): return m
        case .ok: return "Sincronizat"
        }
    }
}

@Observable
@MainActor
final class SyncEngine {
    var status: SyncStatus = .idle
    var lastSyncedAt: Date?
    var pendingCount: Int = 0

    private var modelContext: ModelContext?
    private var syncTask: Task<Void, Never>?

    func attach(context: ModelContext) {
        modelContext = context
        refreshPendingCount()
    }

    func refreshPendingCount() {
        guard let modelContext else {
            pendingCount = 0
            return
        }
        pendingCount = (try? modelContext.fetchCount(FetchDescriptor<OutboxOperation>())) ?? 0
    }

    var hasPendingSync: Bool {
        pendingCount > 0 || status == .syncing
    }

    func enqueue(
        entity: String,
        action: String,
        clientUUID: String? = nil,
        serverId: Int? = nil,
        payload: [String: Any] = [:]
    ) {
        guard let modelContext else { return }
        let sanitized = Self.sanitize(payload)
        let jsonData = (try? JSONSerialization.data(withJSONObject: sanitized)) ?? Data("{}".utf8)
        let json = String(data: jsonData, encoding: .utf8) ?? "{}"
        let op = OutboxOperation(
            entity: entity,
            action: action,
            clientUUID: clientUUID,
            serverId: serverId,
            payloadJSON: json
        )
        modelContext.insert(op)
        try? modelContext.save()
        refreshPendingCount()
        Task { await syncNow() }
    }

    private static func sanitize(_ value: Any) -> Any {
        switch value {
        case let dict as [String: Any]:
            var out: [String: Any] = [:]
            for (k, v) in dict {
                if v is NSNull { continue }
                if case Optional<Any>.none = v as Any? { continue }
                out[k] = sanitize(v)
            }
            return out
        case let arr as [Any]:
            return arr.map { sanitize($0) }
        default:
            return value
        }
    }

    func syncNow() async {
        // Evită sync paralel (ex. Facturi + Clienți + Produse deschise aproape simultan).
        if let syncTask {
            await syncTask.value
            return
        }
        let task = Task { @MainActor in
            await self.performSync()
        }
        syncTask = task
        await task.value
        syncTask = nil
    }

    private func performSync() async {
        guard let modelContext else { return }
        status = .syncing
        do {
            try await pushOutbox(context: modelContext)
            let serverTime = try await pull(context: modelContext)
            status = .ok
            lastSyncedAt = .now
            if let companyId = APIClient.shared.companyId, let serverTime {
                setMeta(context: modelContext, key: Self.syncCursorKey(companyId: companyId), value: serverTime)
            }
            // Cursor vechi global (înainte de sync per firmă) — nu îl mai folosi.
            clearMeta(context: modelContext, key: "last_sync")
            refreshPendingCount()
        } catch let error as APIError {
            if case .offline = error {
                status = .offline
            } else if case .unauthorized = error {
                status = .error("Sesiune expirată")
            } else {
                status = .error(error.localizedDescription)
            }
        } catch {
            status = .error(error.localizedDescription)
        }
    }

    private func pushOutbox(context: ModelContext) async throws {
        let descriptor = FetchDescriptor<OutboxOperation>(sortBy: [SortDescriptor(\.createdAt)])
        let ops = try context.fetch(descriptor)
        guard !ops.isEmpty else { return }

        let operations: [SyncPushOperation] = ops.map { op in
            var payload: [String: AnyCodable] = [:]
            if let data = op.payloadJSON.data(using: .utf8),
               let obj = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
                payload = obj.mapValues { AnyCodable($0) }
            }
            return SyncPushOperation(
                opId: op.opId,
                entity: op.entity,
                action: op.action,
                clientUuid: op.clientUUID,
                serverId: op.serverId,
                payload: payload
            )
        }

        let response: SyncPushResponse = try await APIClient.shared.request(
            "POST",
            path: "sync/push",
            body: SyncPushRequest(operations: operations)
        )

        for result in response.results {
            if let op = ops.first(where: { $0.opId == result.opId }) {
                if result.ok == true {
                    applyPushResult(result, context: context)
                    context.delete(op)
                } else {
                    op.attempts += 1
                    op.lastError = result.error
                }
            }
        }
        try context.save()
    }

    private func applyPushResult(_ result: SyncOpResult, context: ModelContext) {
        guard let uuid = result.clientUuid, let serverId = result.serverId else { return }
        switch result.entity {
        case "client":
            if let local = try? context.fetch(FetchDescriptor<LocalClient>(predicate: #Predicate { $0.clientUUID == uuid })).first {
                local.serverId = serverId
                local.pendingSync = false
            }
        case "product":
            if let local = try? context.fetch(FetchDescriptor<LocalProduct>(predicate: #Predicate { $0.clientUUID == uuid })).first {
                local.serverId = serverId
                local.pendingSync = false
            }
        case "document":
            if let local = try? context.fetch(FetchDescriptor<LocalDocument>(predicate: #Predicate { $0.clientUUID == uuid })).first {
                local.serverId = serverId
                local.pendingSync = false
                local.pendingIssue = false
            }
        case "payment":
            if let local = try? context.fetch(FetchDescriptor<LocalPayment>(predicate: #Predicate { $0.clientUUID == uuid })).first {
                local.serverId = serverId
                local.pendingSync = false
            }
        default:
            break
        }
    }

    /// Cursor incremental separat pe firmă — altfel, după sync pe Firma A, firma B
    /// primește `since` recent și rămâne fără documentele istorice (ex. FLY DAVID).
    private static func syncCursorKey(companyId: Int) -> String {
        "last_sync_company_\(companyId)"
    }

    @discardableResult
    private func pull(context: ModelContext) async throws -> String? {
        guard let activeCompanyId = APIClient.shared.companyId else {
            return nil
        }

        let since = meta(context: context, key: Self.syncCursorKey(companyId: activeCompanyId))
        var afterDocumentId = 0
        var afterPaymentId = 0
        var needDocuments = true
        var needPayments = true
        var serverTime: String?
        var page = 0
        let pageLimit = 500

        while needDocuments || needPayments {
            page += 1
            var query: [URLQueryItem] = []
            if let since {
                query.append(URLQueryItem(name: "since", value: since))
            }
            // Continuare: trimite doar cursorul fluxului care mai are pagini.
            if afterDocumentId > 0, needDocuments {
                query.append(URLQueryItem(name: "after_document_id", value: String(afterDocumentId)))
            }
            if afterPaymentId > 0, needPayments {
                query.append(URLQueryItem(name: "after_payment_id", value: String(afterPaymentId)))
            }

            let response: SyncPullResponse = try await APIClient.shared.request("GET", path: "sync", query: query)
            let companyId = response.company?.id ?? activeCompanyId
            serverTime = response.serverTime

            if page == 1 {
                for c in response.clients {
                    upsertClient(c, companyId: companyId, context: context)
                }
                for p in response.products {
                    upsertProduct(p, companyId: companyId, context: context)
                }
                for s in response.series {
                    upsertSeries(s, companyId: companyId, context: context)
                }
            }

            if needDocuments {
                for d in response.documents {
                    upsertDocument(d, companyId: companyId, context: context)
                    afterDocumentId = max(afterDocumentId, d.id)
                }
                needDocuments = response.hasMoreDocuments ?? (response.documents.count >= pageLimit)
            }
            if needPayments {
                for pay in response.payments {
                    upsertPayment(pay, companyId: companyId, context: context)
                    afterPaymentId = max(afterPaymentId, pay.id)
                }
                needPayments = response.hasMorePayments ?? (response.payments.count >= pageLimit)
            }

            try context.save()
            if page >= 40 { break }
        }

        return serverTime
    }

    private func upsertClient(_ c: APIClientDTO, companyId: Int, context: ModelContext) {
        let sid = c.id
        let existing = try? context.fetch(FetchDescriptor<LocalClient>(predicate: #Predicate { $0.serverId == sid })).first
        let local = existing ?? LocalClient(companyId: companyId > 0 ? companyId : (c.companyId ?? 0), name: c.name)
        if existing == nil { context.insert(local) }
        if local.pendingSync { return }
        local.serverId = c.id
        local.companyId = c.companyId ?? companyId
        local.name = c.name
        local.type = c.type ?? "company"
        local.cui = c.cui
        local.regCom = c.regCom
        local.cnp = c.cnp
        local.address = c.address
        local.city = c.city
        local.county = c.county
        local.country = c.country
        local.phone = c.phone
        local.email = c.email
        local.iban = c.iban
        local.bankName = c.bankName
        local.notes = c.notes
        local.openingBalance = c.openingBalance ?? 0
        local.openingBalanceDate = c.openingBalanceDate
        local.updatedAt = DateFormats.parseISO(c.updatedAt)
        local.isDeleted = false
    }

    private func upsertProduct(_ p: APIProduct, companyId: Int, context: ModelContext) {
        let sid = p.id
        let existing = try? context.fetch(FetchDescriptor<LocalProduct>(predicate: #Predicate { $0.serverId == sid })).first
        let local = existing ?? LocalProduct(companyId: companyId > 0 ? companyId : (p.companyId ?? 0), name: p.name)
        if existing == nil { context.insert(local) }
        if local.pendingSync { return }
        local.serverId = p.id
        local.companyId = p.companyId ?? companyId
        local.name = p.name
        local.sku = p.sku
        local.unit = p.unit ?? "buc"
        local.type = p.type ?? "service"
        local.price = p.price
        local.vatRate = p.vatRate
        local.productDescription = p.description
        local.active = p.active ?? true
        local.updatedAt = DateFormats.parseISO(p.updatedAt)
        local.isDeleted = false
    }

    private func upsertDocument(_ d: APIDocument, companyId: Int, context: ModelContext) {
        let sid = d.id
        let existing = try? context.fetch(FetchDescriptor<LocalDocument>(predicate: #Predicate { $0.serverId == sid })).first
        let local = existing ?? LocalDocument(companyId: companyId > 0 ? companyId : (d.companyId ?? 0), issueDate: d.issueDate ?? DateFormats.today())
        if existing == nil { context.insert(local) }
        if local.pendingSync || local.pendingIssue { return }
        local.serverId = d.id
        local.companyId = d.companyId ?? companyId
        local.clientServerId = d.clientId
        local.type = d.type
        local.status = d.status
        local.series = d.series
        local.number = d.number
        local.numberFull = d.numberFull
        local.issueDate = d.issueDate ?? DateFormats.today()
        local.dueDate = d.dueDate
        local.currency = d.currency ?? "RON"
        local.subtotal = d.subtotal ?? 0
        local.vatTotal = d.vatTotal ?? 0
        local.total = d.total ?? 0
        local.paidAmount = d.paidAmount ?? 0
        local.paymentStatus = d.paymentStatus ?? "unpaid"
        local.notes = d.notes
        local.clientName = d.clientName
        local.clientCui = d.clientCui
        local.clientEmail = d.clientEmail
        local.efacturaStatus = d.efacturaStatus
        local.efacturaError = d.efacturaError
        local.updatedAt = DateFormats.parseISO(d.updatedAt)
        if let items = d.items {
            local.items = items.map {
                LineItemDraft(
                    productId: $0.productId,
                    name: $0.name,
                    unit: $0.unit ?? "buc",
                    quantity: $0.quantity,
                    unitPrice: $0.unitPrice,
                    vatRate: $0.vatRate
                )
            }
        }
        local.isDeleted = false
    }

    private func upsertSeries(_ s: APISeries, companyId: Int, context: ModelContext) {
        let sid = s.id
        let existing = try? context.fetch(FetchDescriptor<LocalSeries>(predicate: #Predicate { $0.serverId == sid })).first
        let year = s.year ?? Calendar.current.component(.year, from: Date())
        let local = existing ?? LocalSeries(
            serverId: sid,
            companyId: companyId,
            type: s.type,
            prefix: s.prefix,
            firstNumber: s.firstNumber ?? s.nextNumber ?? 1,
            nextNumber: s.nextNumber ?? 1,
            year: year,
            active: s.active ?? true,
            isDefault: s.isDefault ?? false
        )
        if existing == nil { context.insert(local) }
        local.companyId = companyId
        local.type = s.type
        local.prefix = s.prefix
        local.firstNumber = s.firstNumber ?? s.nextNumber ?? 1
        local.nextNumber = s.nextNumber ?? 1
        local.year = year
        local.active = s.active ?? true
        local.isDefault = s.isDefault ?? false
        local.updatedAt = .now
    }

    private func upsertPayment(_ p: APIPayment, companyId: Int, context: ModelContext) {
        let sid = p.id
        let existing = try? context.fetch(FetchDescriptor<LocalPayment>(predicate: #Predicate { $0.serverId == sid })).first
        let local = existing ?? LocalPayment(
            companyId: companyId,
            paidAt: p.paidAt ?? DateFormats.today(),
            amount: p.amount
        )
        if existing == nil { context.insert(local) }
        if local.pendingSync { return }
        local.serverId = p.id
        local.companyId = p.companyId ?? companyId
        local.documentServerId = p.documentId
        local.clientServerId = p.clientId
        local.method = p.method ?? "op"
        local.paidAt = p.paidAt ?? DateFormats.today()
        local.amount = p.amount
        local.currency = p.currency ?? "RON"
        local.reference = p.reference
        local.updatedAt = DateFormats.parseISO(p.updatedAt)
    }

    private func meta(context: ModelContext, key: String) -> String? {
        let descriptor = FetchDescriptor<SyncMeta>(predicate: #Predicate { $0.key == key })
        return try? context.fetch(descriptor).first?.value
    }

    private func setMeta(context: ModelContext, key: String, value: String) {
        let descriptor = FetchDescriptor<SyncMeta>(predicate: #Predicate { $0.key == key })
        if let existing = try? context.fetch(descriptor).first {
            existing.value = value
        } else {
            context.insert(SyncMeta(key: key, value: value))
        }
        try? context.save()
    }

    private func clearMeta(context: ModelContext, key: String) {
        let descriptor = FetchDescriptor<SyncMeta>(predicate: #Predicate { $0.key == key })
        if let existing = try? context.fetch(descriptor).first {
            context.delete(existing)
            try? context.save()
        }
    }
}

struct SyncPushRequest: Encodable {
    let operations: [SyncPushOperation]
}

struct SyncPushOperation: Encodable {
    let opId: String
    let entity: String
    let action: String
    let clientUuid: String?
    let serverId: Int?
    let payload: [String: AnyCodable]

    enum CodingKeys: String, CodingKey {
        case entity, action, payload
        case opId = "op_id"
        case clientUuid = "client_uuid"
        case serverId = "server_id"
    }
}

struct AnyCodable: Encodable {
    let value: Any
    init(_ value: Any) { self.value = value }

    func encode(to encoder: Encoder) throws {
        var container = encoder.singleValueContainer()
        switch value {
        case let v as String: try container.encode(v)
        case let v as Int: try container.encode(v)
        case let v as Double: try container.encode(v)
        case let v as Bool: try container.encode(v)
        case let v as [Any]:
            var arr = encoder.unkeyedContainer()
            for item in v { try arr.encode(AnyCodable(item)) }
        case let v as [String: Any]:
            var obj = encoder.container(keyedBy: DynamicKey.self)
            for (k, val) in v {
                try obj.encode(AnyCodable(val), forKey: DynamicKey(stringValue: k)!)
            }
        default:
            try container.encodeNil()
        }
    }
}

private struct DynamicKey: CodingKey {
    var stringValue: String
    init?(stringValue: String) { self.stringValue = stringValue }
    var intValue: Int? { nil }
    init?(intValue: Int) { nil }
}
