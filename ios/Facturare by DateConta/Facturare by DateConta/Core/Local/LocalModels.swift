import Foundation
import SwiftData

@Model
final class LocalClient {
    @Attribute(.unique) var clientUUID: String
    var serverId: Int?
    var companyId: Int
    var name: String
    var type: String
    var cui: String?
    var regCom: String?
    var cnp: String?
    var address: String?
    var city: String?
    var county: String?
    var country: String?
    var phone: String?
    var email: String?
    var iban: String?
    var bankName: String?
    var notes: String?
    var openingBalance: Double
    var openingBalanceDate: String?
    var updatedAt: Date
    var pendingSync: Bool
    var isDeleted: Bool

    init(
        clientUUID: String = UUID().uuidString,
        serverId: Int? = nil,
        companyId: Int,
        name: String,
        type: String = "company",
        cui: String? = nil,
        regCom: String? = nil,
        cnp: String? = nil,
        address: String? = nil,
        city: String? = nil,
        county: String? = nil,
        country: String? = "România",
        phone: String? = nil,
        email: String? = nil,
        iban: String? = nil,
        bankName: String? = nil,
        notes: String? = nil,
        openingBalance: Double = 0,
        openingBalanceDate: String? = nil,
        updatedAt: Date = .now,
        pendingSync: Bool = false,
        isDeleted: Bool = false
    ) {
        self.clientUUID = clientUUID
        self.serverId = serverId
        self.companyId = companyId
        self.name = name
        self.type = type
        self.cui = cui
        self.regCom = regCom
        self.cnp = cnp
        self.address = address
        self.city = city
        self.county = county
        self.country = country
        self.phone = phone
        self.email = email
        self.iban = iban
        self.bankName = bankName
        self.notes = notes
        self.openingBalance = openingBalance
        self.openingBalanceDate = openingBalanceDate
        self.updatedAt = updatedAt
        self.pendingSync = pendingSync
        self.isDeleted = isDeleted
    }
}

@Model
final class LocalProduct {
    @Attribute(.unique) var clientUUID: String
    var serverId: Int?
    var companyId: Int
    var name: String
    var sku: String?
    var unit: String
    var type: String
    var price: Double
    var vatRate: Double
    var productDescription: String?
    var active: Bool
    var updatedAt: Date
    var pendingSync: Bool
    var isDeleted: Bool

    init(
        clientUUID: String = UUID().uuidString,
        serverId: Int? = nil,
        companyId: Int,
        name: String,
        sku: String? = nil,
        unit: String = "buc",
        type: String = "service",
        price: Double = 0,
        vatRate: Double = 21,
        productDescription: String? = nil,
        active: Bool = true,
        updatedAt: Date = .now,
        pendingSync: Bool = false,
        isDeleted: Bool = false
    ) {
        self.clientUUID = clientUUID
        self.serverId = serverId
        self.companyId = companyId
        self.name = name
        self.sku = sku
        self.unit = unit
        self.type = type
        self.price = price
        self.vatRate = vatRate
        self.productDescription = productDescription
        self.active = active
        self.updatedAt = updatedAt
        self.pendingSync = pendingSync
        self.isDeleted = isDeleted
    }
}

@Model
final class LocalDocument {
    @Attribute(.unique) var clientUUID: String
    var serverId: Int?
    var companyId: Int
    var clientServerId: Int?
    var type: String
    var status: String
    var series: String?
    var number: Int?
    var numberFull: String?
    var issueDate: String
    var dueDate: String?
    var currency: String
    var subtotal: Double
    var vatTotal: Double
    var total: Double
    var paidAmount: Double
    var paymentStatus: String
    var notes: String?
    var clientName: String?
    var clientCui: String?
    var clientEmail: String?
    var efacturaStatus: String?
    var efacturaError: String?
    var itemsJSON: String
    var updatedAt: Date
    var pendingSync: Bool
    var pendingIssue: Bool
    var isDeleted: Bool

    init(
        clientUUID: String = UUID().uuidString,
        serverId: Int? = nil,
        companyId: Int,
        clientServerId: Int? = nil,
        type: String = "invoice",
        status: String = "draft",
        series: String? = nil,
        number: Int? = nil,
        numberFull: String? = nil,
        issueDate: String = ISO8601DateFormatter().string(from: Date()).prefix(10).description,
        dueDate: String? = nil,
        currency: String = "RON",
        subtotal: Double = 0,
        vatTotal: Double = 0,
        total: Double = 0,
        paidAmount: Double = 0,
        paymentStatus: String = "unpaid",
        notes: String? = nil,
        clientName: String? = nil,
        clientCui: String? = nil,
        clientEmail: String? = nil,
        efacturaStatus: String? = "none",
        efacturaError: String? = nil,
        itemsJSON: String = "[]",
        updatedAt: Date = .now,
        pendingSync: Bool = false,
        pendingIssue: Bool = false,
        isDeleted: Bool = false
    ) {
        self.clientUUID = clientUUID
        self.serverId = serverId
        self.companyId = companyId
        self.clientServerId = clientServerId
        self.type = type
        self.status = status
        self.series = series
        self.number = number
        self.numberFull = numberFull
        self.issueDate = issueDate
        self.dueDate = dueDate
        self.currency = currency
        self.subtotal = subtotal
        self.vatTotal = vatTotal
        self.total = total
        self.paidAmount = paidAmount
        self.paymentStatus = paymentStatus
        self.notes = notes
        self.clientName = clientName
        self.clientCui = clientCui
        self.clientEmail = clientEmail
        self.efacturaStatus = efacturaStatus
        self.efacturaError = efacturaError
        self.itemsJSON = itemsJSON
        self.updatedAt = updatedAt
        self.pendingSync = pendingSync
        self.pendingIssue = pendingIssue
        self.isDeleted = isDeleted
    }

    var displayTitle: String {
        if let numberFull, !numberFull.isEmpty { return numberFull }
        return "Ciornă \(clientUUID.prefix(8))"
    }

    var items: [LineItemDraft] {
        get {
            guard let data = itemsJSON.data(using: .utf8),
                  let decoded = try? JSONDecoder().decode([LineItemDraft].self, from: data) else {
                return []
            }
            return decoded
        }
        set {
            if let data = try? JSONEncoder().encode(newValue),
               let json = String(data: data, encoding: .utf8) {
                itemsJSON = json
            }
        }
    }
}

struct LineItemDraft: Codable, Identifiable, Hashable {
    var id: String = UUID().uuidString
    var productId: Int?
    var name: String
    var unit: String
    var quantity: Double
    var unitPrice: Double
    var vatRate: Double

    var lineSubtotal: Double { (quantity * unitPrice).rounded(to: 2) }
    var lineVat: Double { (lineSubtotal * vatRate / 100).rounded(to: 2) }
    var lineTotal: Double { (lineSubtotal + lineVat).rounded(to: 2) }
}

@Model
final class LocalSeries {
    @Attribute(.unique) var serverId: Int
    var companyId: Int
    var type: String
    var prefix: String
    var firstNumber: Int
    var nextNumber: Int
    var year: Int
    var active: Bool
    var isDefault: Bool
    var updatedAt: Date

    init(
        serverId: Int,
        companyId: Int,
        type: String,
        prefix: String,
        firstNumber: Int = 1,
        nextNumber: Int = 1,
        year: Int,
        active: Bool = true,
        isDefault: Bool = false,
        updatedAt: Date = .now
    ) {
        self.serverId = serverId
        self.companyId = companyId
        self.type = type
        self.prefix = prefix
        self.firstNumber = firstNumber
        self.nextNumber = nextNumber
        self.year = year
        self.active = active
        self.isDefault = isDefault
        self.updatedAt = updatedAt
    }

    var previewNumberFull: String {
        "\(prefix)-\(String(format: "%04d", nextNumber))"
    }
}

@Model
final class LocalPayment {
    @Attribute(.unique) var clientUUID: String
    var serverId: Int?
    var companyId: Int
    var documentServerId: Int?
    var clientServerId: Int?
    var method: String
    var paidAt: String
    var amount: Double
    var currency: String
    var reference: String?
    var updatedAt: Date
    var pendingSync: Bool

    init(
        clientUUID: String = UUID().uuidString,
        serverId: Int? = nil,
        companyId: Int,
        documentServerId: Int? = nil,
        clientServerId: Int? = nil,
        method: String = "op",
        paidAt: String,
        amount: Double,
        currency: String = "RON",
        reference: String? = nil,
        updatedAt: Date = .now,
        pendingSync: Bool = false
    ) {
        self.clientUUID = clientUUID
        self.serverId = serverId
        self.companyId = companyId
        self.documentServerId = documentServerId
        self.clientServerId = clientServerId
        self.method = method
        self.paidAt = paidAt
        self.amount = amount
        self.currency = currency
        self.reference = reference
        self.updatedAt = updatedAt
        self.pendingSync = pendingSync
    }
}

@Model
final class OutboxOperation {
    @Attribute(.unique) var opId: String
    var entity: String
    var action: String
    var clientUUID: String?
    var serverId: Int?
    var payloadJSON: String
    var createdAt: Date
    var attempts: Int
    var lastError: String?

    init(
        opId: String = UUID().uuidString,
        entity: String,
        action: String,
        clientUUID: String? = nil,
        serverId: Int? = nil,
        payloadJSON: String = "{}",
        createdAt: Date = .now,
        attempts: Int = 0,
        lastError: String? = nil
    ) {
        self.opId = opId
        self.entity = entity
        self.action = action
        self.clientUUID = clientUUID
        self.serverId = serverId
        self.payloadJSON = payloadJSON
        self.createdAt = createdAt
        self.attempts = attempts
        self.lastError = lastError
    }
}

@Model
final class SyncMeta {
    @Attribute(.unique) var key: String
    var value: String

    init(key: String, value: String) {
        self.key = key
        self.value = value
    }
}

extension Double {
    func rounded(to places: Int) -> Double {
        let p = pow(10.0, Double(places))
        return (self * p).rounded() / p
    }
}

enum DateFormats {
    static func today() -> String {
        let f = DateFormatter()
        f.calendar = Calendar(identifier: .gregorian)
        f.locale = Locale(identifier: "en_US_POSIX")
        f.dateFormat = "yyyy-MM-dd"
        return f.string(from: Date())
    }

    static func parseISO(_ value: String?) -> Date {
        guard let value else { return .now }
        let f = ISO8601DateFormatter()
        f.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        if let d = f.date(from: value) { return d }
        f.formatOptions = [.withInternetDateTime]
        return f.date(from: value) ?? .now
    }
}
