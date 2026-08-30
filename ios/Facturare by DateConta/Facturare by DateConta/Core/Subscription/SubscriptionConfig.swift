import Foundation

enum SubscriptionConfig {
    /// Product IDs — trebuie identice în App Store Connect + Products.storekit + backend.
    static let monthlyProductId = "ro.dateconta.facturare.premium.monthly"
    static let threeMonthsProductId = "ro.dateconta.facturare.premium.3months"
    static let sixMonthsProductId = "ro.dateconta.facturare.premium.6months"
    static let yearlyProductId = "ro.dateconta.facturare.premium.yearly"

    static let allProductIds: [String] = [
        monthlyProductId,
        threeMonthsProductId,
        sixMonthsProductId,
        yearlyProductId,
    ]

    static let freeUntilISO = "2027-03-31"

    /// Ordine afișare în paywall (scurt → lung).
    static func sortKey(for productId: String) -> Int {
        switch productId {
        case monthlyProductId: return 0
        case threeMonthsProductId: return 1
        case sixMonthsProductId: return 2
        case yearlyProductId: return 3
        default: return 99
        }
    }

    static func periodLabel(for productId: String) -> String {
        switch productId {
        case monthlyProductId: return "1 lună"
        case threeMonthsProductId: return "3 luni"
        case sixMonthsProductId: return "6 luni"
        case yearlyProductId: return "1 an"
        default: return productId
        }
    }

    static var freeUntilDate: Date {
        var cal = Calendar(identifier: .gregorian)
        cal.timeZone = TimeZone(identifier: "Europe/Bucharest") ?? .current
        var c = DateComponents()
        c.year = 2027
        c.month = 3
        c.day = 31
        c.hour = 23
        c.minute = 59
        c.second = 59
        return cal.date(from: c) ?? Date.distantPast
    }

    static var isInFreePeriod: Bool {
        Date() <= freeUntilDate
    }

    static func isKnownProduct(_ productId: String) -> Bool {
        allProductIds.contains(productId)
    }
}
