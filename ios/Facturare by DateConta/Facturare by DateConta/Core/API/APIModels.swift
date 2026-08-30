import Foundation

struct AuthResponse: Decodable {
    let token: String?
    let tokenType: String?
    let user: APIUser
    let companies: [APICompany]
    let appVersion: String?

    enum CodingKeys: String, CodingKey {
        case token, user, companies
        case tokenType = "token_type"
        case appVersion = "app_version"
    }
}

struct MeResponse: Decodable {
    let user: APIUser
    let companies: [APICompany]
    let appVersion: String?

    enum CodingKeys: String, CodingKey {
        case user, companies
        case appVersion = "app_version"
    }
}

struct APIUser: Codable, Identifiable {
    let id: Int
    let name: String
    let email: String
    let plan: String?
    let uiLocale: String?
    let isAdmin: Bool?
    let hasAccess: Bool?
    let accessLabel: String?
    let currentCompanyId: Int?
    let canManageCompanyUsers: Bool?

    enum CodingKeys: String, CodingKey {
        case id, name, email, plan
        case uiLocale = "ui_locale"
        case isAdmin = "is_admin"
        case hasAccess = "has_access"
        case accessLabel = "access_label"
        case currentCompanyId = "current_company_id"
        case canManageCompanyUsers = "can_manage_company_users"
    }
}

struct APICompany: Codable, Identifiable, Hashable {
    let id: Int
    let name: String
    let cui: String?
    let promoCode: String?
    let regCom: String?
    let address: String?
    let city: String?
    let county: String?
    let country: String?
    let phone: String?
    let email: String?
    let iban: String?
    let bankName: String?
    let vatPayer: Bool?
    let defaultVatRate: Double?
    let efacturaSendMode: String?
    let anafAuthorized: Bool?
    let role: String?
    let permissions: [String]?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id, name, cui, address, city, county, country, phone, email, iban, role, permissions
        case promoCode = "promo_code"
        case regCom = "reg_com"
        case bankName = "bank_name"
        case vatPayer = "vat_payer"
        case defaultVatRate = "default_vat_rate"
        case efacturaSendMode = "efactura_send_mode"
        case anafAuthorized = "anaf_authorized"
        case updatedAt = "updated_at"
    }

    func can(_ ability: String) -> Bool {
        if role == "owner" { return true }
        return permissions?.contains(ability) == true
    }
}

struct APIClientDTO: Codable, Identifiable {
    let id: Int
    let companyId: Int?
    let name: String
    let type: String?
    let cui: String?
    let regCom: String?
    let cnp: String?
    let address: String?
    let city: String?
    let county: String?
    let country: String?
    let phone: String?
    let email: String?
    let iban: String?
    let bankName: String?
    let notes: String?
    let openingBalance: Double?
    let openingBalanceDate: String?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id, name, type, cui, cnp, address, city, county, country, phone, email, iban, notes
        case companyId = "company_id"
        case regCom = "reg_com"
        case bankName = "bank_name"
        case openingBalance = "opening_balance"
        case openingBalanceDate = "opening_balance_date"
        case updatedAt = "updated_at"
    }
}

struct APIProduct: Codable, Identifiable {
    let id: Int
    let companyId: Int?
    let name: String
    let sku: String?
    let unit: String?
    let type: String?
    let price: Double
    let vatRate: Double
    let description: String?
    let active: Bool?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id, name, sku, unit, type, price, description, active
        case companyId = "company_id"
        case vatRate = "vat_rate"
        case updatedAt = "updated_at"
    }
}

struct APIDocumentItem: Codable, Identifiable {
    var id: Int?
    var productId: Int?
    var name: String
    var description: String?
    var unit: String?
    var quantity: Double
    var unitPrice: Double
    var vatRate: Double
    var subtotal: Double?
    var vatAmount: Double?
    var total: Double?

    enum CodingKeys: String, CodingKey {
        case id, name, description, unit, quantity, total
        case productId = "product_id"
        case unitPrice = "unit_price"
        case vatRate = "vat_rate"
        case subtotal
        case vatAmount = "vat_amount"
    }
}

struct APIDocument: Codable, Identifiable {
    let id: Int
    let companyId: Int?
    let clientId: Int?
    let type: String
    let status: String
    let series: String?
    let number: Int?
    let numberFull: String?
    let issueDate: String?
    let dueDate: String?
    let currency: String?
    let subtotal: Double?
    let vatTotal: Double?
    let total: Double?
    let paidAmount: Double?
    let paymentStatus: String?
    let notes: String?
    let clientName: String?
    let clientCui: String?
    let clientEmail: String?
    let efacturaStatus: String?
    let efacturaError: String?
    let updatedAt: String?
    let items: [APIDocumentItem]?

    enum CodingKeys: String, CodingKey {
        case id, type, status, series, number, currency, notes, items
        case companyId = "company_id"
        case clientId = "client_id"
        case numberFull = "number_full"
        case issueDate = "issue_date"
        case dueDate = "due_date"
        case subtotal
        case vatTotal = "vat_total"
        case total
        case paidAmount = "paid_amount"
        case paymentStatus = "payment_status"
        case clientName = "client_name"
        case clientCui = "client_cui"
        case clientEmail = "client_email"
        case efacturaStatus = "efactura_status"
        case efacturaError = "efactura_error"
        case updatedAt = "updated_at"
    }

    var displayTitle: String {
        if let numberFull, !numberFull.isEmpty { return numberFull }
        return "Ciornă #\(id)"
    }
}

struct APIPayment: Codable, Identifiable {
    let id: Int
    let companyId: Int?
    let documentId: Int?
    let clientId: Int?
    let method: String?
    let paidAt: String?
    let amount: Double
    let currency: String?
    let reference: String?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id, method, amount, currency, reference
        case companyId = "company_id"
        case documentId = "document_id"
        case clientId = "client_id"
        case paidAt = "paid_at"
        case updatedAt = "updated_at"
    }
}

struct APIRecurring: Codable, Identifiable {
    let id: Int
    let clientId: Int?
    let clientName: String?
    let title: String?
    let frequency: String?
    let startDate: String?
    let nextRunDate: String?
    let currency: String?
    let documentType: String?
    let series: String?
    let active: Bool?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id, title, frequency, currency, series, active
        case clientId = "client_id"
        case clientName = "client_name"
        case startDate = "start_date"
        case nextRunDate = "next_run_date"
        case documentType = "document_type"
        case updatedAt = "updated_at"
    }
}

struct DataEnvelope<T: Decodable>: Decodable {
    let data: T
}

struct SyncPullResponse: Decodable {
    let serverTime: String
    let company: SyncCompany?
    let clients: [APIClientDTO]
    let products: [APIProduct]
    let documents: [APIDocument]
    let payments: [APIPayment]
    let series: [APISeries]
    let recurring: [APIRecurring]
    let hasMoreDocuments: Bool?
    let hasMorePayments: Bool?

    enum CodingKeys: String, CodingKey {
        case company, clients, products, documents, payments, series, recurring
        case serverTime = "server_time"
        case hasMoreDocuments = "has_more_documents"
        case hasMorePayments = "has_more_payments"
    }
}

struct SyncCompany: Decodable {
    let id: Int
    let name: String
    let cui: String?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id, name, cui
        case updatedAt = "updated_at"
    }
}

struct APISeries: Codable, Identifiable {
    let id: Int
    let type: String
    let prefix: String
    let firstNumber: Int?
    let nextNumber: Int?
    let year: Int?
    let active: Bool?
    let isDefault: Bool?

    enum CodingKeys: String, CodingKey {
        case id, type, prefix, year, active
        case firstNumber = "first_number"
        case nextNumber = "next_number"
        case isDefault = "is_default"
    }
}

struct SyncPushResponse: Decodable {
    let serverTime: String
    let results: [SyncOpResult]

    enum CodingKeys: String, CodingKey {
        case results
        case serverTime = "server_time"
    }
}

struct SyncOpResult: Decodable {
    let opId: String
    let entity: String
    let action: String
    let clientUuid: String?
    let ok: Bool?
    let serverId: Int?
    let error: String?
    let deleted: Bool?

    enum CodingKeys: String, CodingKey {
        case entity, action, error, deleted
        case opId = "op_id"
        case clientUuid = "client_uuid"
        case ok
        case serverId = "server_id"
    }
}

struct DashboardResponse: Decodable {
    let company: SyncCompany
    let accessLabel: String?
    let stats: DashboardStats
    let unpaid: [DashboardDocRow]
    let drafts: [DashboardDocRow]?
    let recentDocuments: [DashboardDocRow]?

    enum CodingKeys: String, CodingKey {
        case company, stats, unpaid, drafts
        case accessLabel = "access_label"
        case recentDocuments = "recent_documents"
    }
}

struct DashboardStats: Decodable {
    let clientsReceivableToday: Double?
    let invoicesTodayTotal: Double?
    let invoicesMonthCount: Int
    let invoicesMonthTotal: Double
    let paymentsTodayTotal: Double?
    let paymentsMonthTotal: Double
    let paymentsTodayByMethod: PaymentMethodBreakdown?
    let paymentsMonthByMethod: PaymentMethodBreakdown?
    let unpaidCount: Int?
    let draftsCount: Int?
    let clientsCount: Int?
    let productsCount: Int?

    enum CodingKeys: String, CodingKey {
        case invoicesTodayTotal = "invoices_today_total"
        case invoicesMonthCount = "invoices_month_count"
        case invoicesMonthTotal = "invoices_month_total"
        case paymentsTodayTotal = "payments_today_total"
        case paymentsMonthTotal = "payments_month_total"
        case paymentsTodayByMethod = "payments_today_by_method"
        case paymentsMonthByMethod = "payments_month_by_method"
        case clientsReceivableToday = "clients_receivable_today"
        case unpaidCount = "unpaid_count"
        case draftsCount = "drafts_count"
        case clientsCount = "clients_count"
        case productsCount = "products_count"
    }
}

struct PaymentMethodBreakdown: Decodable {
    let cash: Double?
    let card: Double?
    let op: Double?
    let other: Double?

    var lines: [(String, Double)] {
        var rows: [(String, Double)] = [
            ("Cash", cash ?? 0),
            ("Card", card ?? 0),
            ("OP", op ?? 0),
        ]
        if (other ?? 0) > 0.009 {
            rows.append(("Altele", other ?? 0))
        }
        return rows
    }
}

struct DashboardDocRow: Decodable, Identifiable {
    let id: Int
    let type: String?
    let status: String?
    let numberFull: String?
    let clientName: String?
    let total: Double?
    let remaining: Double?
    let dueDate: String?
    let issueDate: String?
    let paymentStatus: String?
    let typeLabel: String?

    enum CodingKeys: String, CodingKey {
        case id, type, status, total, remaining
        case numberFull = "number_full"
        case clientName = "client_name"
        case dueDate = "due_date"
        case issueDate = "issue_date"
        case paymentStatus = "payment_status"
        case typeLabel = "type_label"
    }

    var displayTitle: String {
        if let numberFull, !numberFull.isEmpty { return numberFull }
        if let typeLabel, !typeLabel.isEmpty { return typeLabel }
        return type?.capitalized ?? "#\(id)"
    }
}

struct ReportSummary: Decodable {
    let from: String
    let to: String
    let salesTotal: Double
    let paymentsTotal: Double
    let unpaidTotal: Double
    let documentsCount: Int

    enum CodingKeys: String, CodingKey {
        case from, to
        case salesTotal = "sales_total"
        case paymentsTotal = "payments_total"
        case unpaidTotal = "unpaid_total"
        case documentsCount = "documents_count"
    }
}

struct EfacturaStatusResponse: Decodable {
    let authorized: Bool
    let anafCif: String?
    let sendMode: String?
    let oauthUrl: String?
    let webSettingsUrl: String?

    enum CodingKeys: String, CodingKey {
        case authorized
        case anafCif = "anaf_cif"
        case sendMode = "send_mode"
        case oauthUrl = "oauth_url"
        case webSettingsUrl = "web_settings_url"
    }
}

struct APIErrorBody: Decodable {
    let message: String?
    let code: String?
}

struct CompanyUserRow: Codable, Identifiable {
    let id: Int
    let name: String
    let email: String
    let isSubuser: Bool?
    let isInvited: Bool?
    let memberships: [CompanyMembership]?

    enum CodingKeys: String, CodingKey {
        case id, name, email, memberships
        case isSubuser = "is_subuser"
        case isInvited = "is_invited"
    }
}

struct CompanyMembership: Codable {
    let companyId: Int
    let companyName: String?
    let role: String?
    let permissions: [String]?

    enum CodingKeys: String, CodingKey {
        case role, permissions
        case companyId = "company_id"
        case companyName = "company_name"
    }
}

// MARK: - Admin

struct AdminStatsResponse: Decodable {
    let data: AdminStatsData
}

struct AdminStatsData: Decodable {
    let visitors: AdminVisitorPeriods
    let totals: AdminTotals
    let topCountries: [AdminNamedCount]
    let topBrowsers: [AdminLabelCount]
    let topOperatingSystems: [AdminLabelCount]
    let activeVisitors: [AdminActiveVisitor]
    let users: [AdminUserRow]

    enum CodingKeys: String, CodingKey {
        case visitors, totals, users
        case topCountries = "top_countries"
        case topBrowsers = "top_browsers"
        case topOperatingSystems = "top_operating_systems"
        case activeVisitors = "active_visitors"
    }
}

struct AdminVisitorPeriods: Decodable {
    let all: AdminVisitorStat
    let month: AdminVisitorStat
    let week: AdminVisitorStat
    let active: AdminVisitorStat
}

struct AdminVisitorStat: Decodable {
    let unique: Int
    let total: Int
}

struct AdminTotals: Decodable {
    let users: Int
    let usersWeek: Int
    let usersLogged: Int
    let companies: Int
    let clients: Int
    let invoicesIssued: Int
    let invoicesMonth: Int
    let paymentsMonth: Double
    let invoiceTotalMonth: Double

    enum CodingKeys: String, CodingKey {
        case users, companies, clients
        case usersWeek = "users_week"
        case usersLogged = "users_logged"
        case invoicesIssued = "invoices_issued"
        case invoicesMonth = "invoices_month"
        case paymentsMonth = "payments_month"
        case invoiceTotalMonth = "invoice_total_month"
    }
}

struct AdminNamedCount: Decodable, Identifiable {
    var id: String { code ?? name ?? UUID().uuidString }
    let code: String?
    let name: String?
    let visitors: Int
}

struct AdminLabelCount: Decodable, Identifiable {
    var id: String { label }
    let label: String
    let visitors: Int
}

struct AdminActiveVisitor: Decodable, Identifiable {
    var id: String { "\(lastSeenAt ?? "")-\(ip ?? "")-\(path ?? "")" }
    let lastSeenAt: String?
    let country: String?
    let countryCode: String?
    let browser: String?
    let os: String?
    let userEmail: String?
    let path: String?
    let ip: String?

    enum CodingKeys: String, CodingKey {
        case country, browser, os, path, ip
        case lastSeenAt = "last_seen_at"
        case countryCode = "country_code"
        case userEmail = "user_email"
    }
}

struct AdminUserRow: Codable, Identifiable {
    let id: Int
    let name: String
    let email: String
    let plan: String?
    let isAdmin: Bool?
    let createdAt: String?
    let companiesCount: Int?
    let ownedCompaniesCount: Int?
    let isLoggedNow: Bool?
    let accessLabel: String?
    let accessUntil: String?
    let lastSeenAt: String?

    enum CodingKeys: String, CodingKey {
        case id, name, email, plan
        case isAdmin = "is_admin"
        case createdAt = "created_at"
        case companiesCount = "companies_count"
        case ownedCompaniesCount = "owned_companies_count"
        case isLoggedNow = "is_logged_now"
        case accessLabel = "access_label"
        case accessUntil = "access_until"
        case lastSeenAt = "last_seen_at"
    }
}

struct AdminCompanyRow: Codable, Identifiable {
    let id: Int
    let name: String
    let cui: String?
    let promoCode: String?
    let ownerName: String?
    let ownerEmail: String?
    let ownerIsAdmin: Bool?
    let accessLabel: String?
    let accessUntil: String?

    enum CodingKeys: String, CodingKey {
        case id, name, cui
        case promoCode = "promo_code"
        case ownerName = "owner_name"
        case ownerEmail = "owner_email"
        case ownerIsAdmin = "owner_is_admin"
        case accessLabel = "access_label"
        case accessUntil = "access_until"
    }
}
