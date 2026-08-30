import Foundation
import Observation

@Observable
@MainActor
final class AuthStore {
    private let tokenKey = "auth_token"
    private let companyKey = "current_company_id"
    private let userKey = "cached_user_json"
    private let companiesKey = "cached_companies_json"
    private let webVersionKey = "cached_web_app_version"

    var token: String?
    var user: APIUser?
    var companies: [APICompany] = []
    var currentCompanyId: Int?
    /// Versiunea aplicației web (din API / changelog).
    var webAppVersion: String?
    var isLoading = false
    var errorMessage: String?

    var isAuthenticated: Bool { token != nil && user != nil }
    var isAdmin: Bool { user?.isAdmin == true }

    var currentCompany: APICompany? {
        companies.first(where: { $0.id == currentCompanyId }) ?? companies.first
    }

    init() {
        token = KeychainStore.string(forKey: tokenKey)
        if let raw = UserDefaults.standard.string(forKey: companyKey), let id = Int(raw) {
            currentCompanyId = id
        }
        if let data = UserDefaults.standard.data(forKey: userKey),
           let user = try? JSONDecoder().decode(APIUser.self, from: data) {
            self.user = user
        }
        if let data = UserDefaults.standard.data(forKey: companiesKey),
           let companies = try? JSONDecoder().decode([APICompany].self, from: data) {
            self.companies = companies
        }
        webAppVersion = UserDefaults.standard.string(forKey: webVersionKey)
        APIClient.shared.setCredentials(token: token, companyId: currentCompanyId)
    }

    func login(email: String, password: String) async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let body: [String: String] = [
                "email": email,
                "password": password,
                "device_name": APIConfig.deviceName,
            ]
            let response: AuthResponse = try await APIClient.shared.request(
                "POST", path: "login", body: body, authorized: false
            )
            applyAuth(response)
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func register(name: String, email: String, password: String, passwordConfirmation: String) async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let body: [String: String] = [
                "name": name,
                "email": email,
                "password": password,
                "password_confirmation": passwordConfirmation,
                "device_name": APIConfig.deviceName,
            ]
            let response: AuthResponse = try await APIClient.shared.request(
                "POST", path: "register", body: body, authorized: false
            )
            applyAuth(response)
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func refreshMe() async {
        guard token != nil else { return }
        do {
            let response: MeResponse = try await APIClient.shared.request("GET", path: "me")
            user = response.user
            companies = Self.sanitizeCompanies(response.companies, for: response.user)
            if let version = response.appVersion, !version.isEmpty {
                webAppVersion = version
                UserDefaults.standard.set(version, forKey: webVersionKey)
            }
            persistUserCache()
            // Preferă firma activă de pe server; evită să rămână un id vechi din cache (ex. FLY DAVID).
            let serverCompanyId = response.user.currentCompanyId
            let resolved: Int? = {
                if let id = serverCompanyId, companies.contains(where: { $0.id == id }) {
                    return id
                }
                return companies.first?.id
            }()
            if currentCompanyId != resolved {
                currentCompanyId = resolved
                persistCompany()
            }
            APIClient.shared.setCredentials(token: token, companyId: currentCompanyId)
        } catch {
            if case APIError.unauthorized = error {
                logoutLocal()
            }
        }
    }

    func switchCompany(_ company: APICompany) async {
        guard company.id != currentCompanyId else { return }
        let previousId = currentCompanyId
        // Header-ul API înainte de UI, ca orice reload să ceară deja firma nouă.
        APIClient.shared.setCompanyId(company.id)
        do {
            let _: DataEnvelope<APICompany> = try await APIClient.shared.request(
                "POST", path: "companies/\(company.id)/switch"
            )
            currentCompanyId = company.id
            persistCompany()
        } catch {
            APIClient.shared.setCompanyId(previousId)
            errorMessage = error.localizedDescription
        }
    }

    func logout() async {
        if token != nil {
            _ = try? await APIClient.shared.rawRequest("POST", path: "logout")
        }
        logoutLocal()
    }

    /// Ștergere cont (App Store Guideline 5.1.1) — confirm + parolă.
    func deleteAccount(password: String) async -> Bool {
        struct Body: Encodable {
            let password: String
            let confirm: Bool
        }
        errorMessage = nil
        do {
            struct Resp: Decodable { let deleted: Bool? }
            let _: Resp = try await APIClient.shared.request(
                "DELETE",
                path: "profile",
                body: Body(password: password, confirm: true)
            )
            logoutLocal()
            return true
        } catch {
            errorMessage = error.localizedDescription
            return false
        }
    }

    func can(_ ability: String) -> Bool {
        currentCompany?.can(ability) ?? false
    }

    private func applyAuth(_ response: AuthResponse) {
        guard let token = response.token else {
            errorMessage = "Token lipsă."
            return
        }
        self.token = token
        self.user = response.user
        self.companies = Self.sanitizeCompanies(response.companies, for: response.user)
        if let version = response.appVersion, !version.isEmpty {
            webAppVersion = version
            UserDefaults.standard.set(version, forKey: webVersionKey)
        }
        let preferred = response.user.currentCompanyId
        if let preferred, companies.contains(where: { $0.id == preferred }) {
            self.currentCompanyId = preferred
        } else {
            self.currentCompanyId = companies.first?.id
        }
        KeychainStore.set(token, forKey: tokenKey)
        persistCompany()
        persistUserCache()
        APIClient.shared.setCredentials(token: token, companyId: currentCompanyId)
    }

    /// Contul demo nu trebuie să opereze pe firma operator (FLY DAVID).
    private static func sanitizeCompanies(_ companies: [APICompany], for user: APIUser?) -> [APICompany] {
        guard user?.email.lowercased() == "demo@dateconta.ro" else { return companies }
        let filtered = companies.filter { company in
            let digits = (company.cui ?? "").filter(\.isNumber)
            return digits != "38254880"
        }
        return filtered.isEmpty ? companies : filtered
    }

    private func logoutLocal() {
        token = nil
        user = nil
        companies = []
        currentCompanyId = nil
        webAppVersion = nil
        KeychainStore.delete(forKey: tokenKey)
        UserDefaults.standard.removeObject(forKey: companyKey)
        UserDefaults.standard.removeObject(forKey: userKey)
        UserDefaults.standard.removeObject(forKey: companiesKey)
        UserDefaults.standard.removeObject(forKey: webVersionKey)
        APIClient.shared.setCredentials(token: nil, companyId: nil)
    }

    private func persistCompany() {
        if let currentCompanyId {
            UserDefaults.standard.set(String(currentCompanyId), forKey: companyKey)
        } else {
            UserDefaults.standard.removeObject(forKey: companyKey)
        }
    }

    private func persistUserCache() {
        if let user, let data = try? JSONEncoder().encode(user) {
            UserDefaults.standard.set(data, forKey: userKey)
        }
        if let data = try? JSONEncoder().encode(companies) {
            UserDefaults.standard.set(data, forKey: companiesKey)
        }
    }
}
