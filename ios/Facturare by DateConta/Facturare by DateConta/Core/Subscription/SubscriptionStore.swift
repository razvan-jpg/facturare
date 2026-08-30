import Foundation
import StoreKit
import UIKit

struct IosSubscriptionStatus: Decodable, Sendable {
    let freeUntil: String?
    let inFreePeriod: Bool?
    let forcePaywall: Bool?
    let trialEndsAt: String?
    let inTrial: Bool?
    let productId: String?
    let productIds: [String]?
    let hasAccess: Bool
    let hasEntitlement: Bool?
    let expiresAt: String?
    let status: String?
    let environment: String?
    let note: String?

    enum CodingKeys: String, CodingKey {
        case freeUntil = "free_until"
        case inFreePeriod = "in_free_period"
        case forcePaywall = "force_paywall"
        case trialEndsAt = "trial_ends_at"
        case inTrial = "in_trial"
        case productId = "product_id"
        case productIds = "product_ids"
        case hasAccess = "has_access"
        case hasEntitlement = "has_entitlement"
        case expiresAt = "expires_at"
        case status, environment, note
    }
}

@MainActor
@Observable
final class SubscriptionStore {
    private(set) var products: [Product] = []
    private(set) var selectedProductId: String = SubscriptionConfig.monthlyProductId
    private(set) var serverStatus: IosSubscriptionStatus?
    private(set) var localEntitled = false
    private(set) var isLoading = false
    private(set) var purchaseInFlight = false
    private(set) var errorMessage: String?
    private(set) var lastSyncedAt: Date?

    /// Acces app: status server (sursă de adevăr) SAU entitlement local SAU perioadă gratuită locală.
    var hasAccess: Bool {
        if let server = serverStatus {
            return server.hasAccess
        }
        if localEntitled { return true }
        if SubscriptionConfig.isInFreePeriod { return true }
        return false
    }

    var isInFreePeriod: Bool {
        if serverStatus?.forcePaywall == true { return false }
        return serverStatus?.inFreePeriod ?? SubscriptionConfig.isInFreePeriod
    }

    var isInTrial: Bool {
        serverStatus?.inTrial == true
    }

    var trialEndsLabel: String? {
        guard let raw = serverStatus?.trialEndsAt else { return nil }
        return Self.formatISO(raw)
    }

    var expiresLabel: String? {
        guard let raw = serverStatus?.expiresAt else { return nil }
        return Self.formatISO(raw)
    }

    var selectedProduct: Product? {
        products.first(where: { $0.id == selectedProductId }) ?? products.first
    }

    var sortedProducts: [Product] {
        products.sorted { SubscriptionConfig.sortKey(for: $0.id) < SubscriptionConfig.sortKey(for: $1.id) }
    }

    init() {
        Task { await listenForTransactions() }
    }

    func start() async {
        await loadProducts()
        await refreshLocalEntitlements()
        await refreshServerStatus()
    }

    func selectProduct(_ id: String) {
        selectedProductId = id
    }

    func loadProducts() async {
        do {
            let loaded = try await Product.products(for: SubscriptionConfig.allProductIds)
            products = loaded
            if !loaded.contains(where: { $0.id == selectedProductId }) {
                selectedProductId = loaded.first?.id ?? SubscriptionConfig.monthlyProductId
            }
            if loaded.isEmpty {
                errorMessage = "Produsele de abonament nu sunt disponibile. Activează Products.storekit în Scheme sau publică IAP în App Store Connect."
            }
        } catch {
            errorMessage = "Nu pot încărca produsele de abonament: \(error.localizedDescription)"
        }
    }

    func refreshLocalEntitlements() async {
        var entitled = false
        for await result in Transaction.currentEntitlements {
            guard case .verified(let tx) = result else { continue }
            if SubscriptionConfig.isKnownProduct(tx.productID),
               tx.revocationDate == nil,
               (tx.expirationDate ?? .distantFuture) > Date() {
                entitled = true
                await verifyOnServer(jws: result.jwsRepresentation)
            }
        }
        localEntitled = entitled
    }

    func refreshServerStatus() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let status: IosSubscriptionStatus = try await APIClient.shared.request(
                "GET", path: "ios/subscription/status"
            )
            serverStatus = status
            lastSyncedAt = Date()
            errorMessage = nil
        } catch APIError.unauthorized {
            serverStatus = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func purchase() async {
        if products.isEmpty {
            await loadProducts()
        }
        guard let product = selectedProduct else {
            errorMessage = "Produsul de abonament nu este disponibil. Activează Products.storekit în Scheme sau publică IAP în App Store Connect."
            return
        }
        await purchase(product: product)
    }

    func purchase(product: Product) async {
        selectedProductId = product.id
        purchaseInFlight = true
        errorMessage = nil
        defer { purchaseInFlight = false }
        do {
            let result = try await product.purchase()
            switch result {
            case .success(let verification):
                guard case .verified(let tx) = verification else {
                    errorMessage = "Tranzacție neverificată."
                    return
                }
                await tx.finish()
                localEntitled = true
                await verifyOnServer(jws: verification.jwsRepresentation)
                await refreshServerStatus()
            case .userCancelled:
                break
            case .pending:
                errorMessage = "Plata este în așteptare (aprobare parentală / Ask to Buy)."
            @unknown default:
                break
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func restore() async {
        purchaseInFlight = true
        errorMessage = nil
        defer { purchaseInFlight = false }
        do {
            try await AppStore.sync()
            await refreshLocalEntitlements()
            await refreshServerStatus()
            if !hasAccess {
                errorMessage = "Nu am găsit un abonament activ de restaurat."
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func manageSubscriptions() async {
        guard let scene = UIApplication.shared.connectedScenes.first as? UIWindowScene else { return }
        do {
            try await AppStore.showManageSubscriptions(in: scene)
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func verifyOnServer(jws: String) async {
        struct Body: Encodable {
            let signedTransaction: String
            enum CodingKeys: String, CodingKey { case signedTransaction = "signed_transaction" }
        }
        do {
            let status: IosSubscriptionStatus = try await APIClient.shared.request(
                "POST",
                path: "ios/subscription/verify",
                body: Body(signedTransaction: jws)
            )
            serverStatus = status
            lastSyncedAt = Date()
        } catch {
            // Offline: entitlement local rămâne.
        }
    }

    private func listenForTransactions() async {
        for await result in Transaction.updates {
            guard case .verified(let tx) = result else { continue }
            await tx.finish()
            if SubscriptionConfig.isKnownProduct(tx.productID) {
                await refreshLocalEntitlements()
                await verifyOnServer(jws: result.jwsRepresentation)
                await refreshServerStatus()
            }
        }
    }

    private static func formatISO(_ raw: String) -> String? {
        let freestyle = ISO8601DateFormatter()
        freestyle.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        let plain = ISO8601DateFormatter()
        let date = freestyle.date(from: raw) ?? plain.date(from: raw)
        guard let date else { return raw }
        let out = DateFormatter()
        out.locale = Locale(identifier: "ro_RO")
        out.dateStyle = .medium
        out.timeStyle = .none
        return out.string(from: date)
    }
}
