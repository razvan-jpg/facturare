import SwiftUI
import UIKit

struct SettingsView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(SubscriptionStore.self) private var subscription
    @State private var users: [CompanyUserRow] = []
    @State private var companyName = ""
    @State private var companyCui = ""
    @State private var companyEmail = ""
    @State private var companyPhone = ""
    @State private var invoiceNotes = ""
    @State private var promoCode = ""
    @State private var message: String?
    @State private var showCreateCompany = false
    @State private var showReferralMail = false
    @State private var profileName = ""
    @State private var copiedPromo = false
    @State private var showDeleteAccount = false
    @State private var deletePassword = ""
    @State private var deleteInFlight = false

    var body: some View {
        Form {
            Section {
                LabeledContent("Email", value: auth.user?.email ?? "—")
                TextField("Nume", text: $profileName)
                Button("Salvează profil") { Task { await saveProfile() } }
            } header: {
                Text("Cont")
            } footer: {
                Text("Ca să intri cu alt utilizator, apasă „Ieși din cont”, apoi autentifică-te din nou.")
            }

            Section {
                if subscription.isInFreePeriod {
                    LabeledContent("Acces iOS", value: "Gratuit până la 31.03.2027")
                    Text("După această dată: conturile noi au 1 lună de test pe iOS, apoi abonament App Store (1 / 3 / 6 luni sau 1 an). Conturile existente trec pe abonament.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                } else if subscription.isInTrial {
                    LabeledContent("Acces iOS", value: "Perioadă de test")
                    if let trialEnds = subscription.trialEndsLabel {
                        LabeledContent("Test până la", value: trialEnds)
                    }
                    Text("După perioada de test este necesar un abonament App Store (doar app-ul iOS).")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                } else if subscription.hasAccess {
                    LabeledContent("Abonament iOS", value: "Activ")
                    if let expires = subscription.expiresLabel {
                        LabeledContent("Expiră", value: expires)
                    }
                    Button("Gestionează în App Store") {
                        Task { await subscription.manageSubscriptions() }
                    }
                } else {
                    Text("Abonament iOS necesar (1 / 3 / 6 luni sau 1 an)")
                        .foregroundStyle(.secondary)
                    Button("Abonează-te") {
                        Task { await subscription.purchase() }
                    }
                }
                Button("Restaurează cumpărăturile") {
                    Task { await subscription.restore() }
                }
                if let err = subscription.errorMessage {
                    Text(err).font(.footnote).foregroundStyle(.red)
                }
            } header: {
                Text("Abonament aplicație")
            } footer: {
                Text("Abonamentul din App Store deblochează doar app-ul iOS. Abonamentul web (card/OP) se gestionează separat pe site.")
            }

            Section {
                Button("Ieși din cont", role: .destructive) {
                    Task { await auth.logout() }
                }
                Button("Șterge contul", role: .destructive) {
                    deletePassword = ""
                    showDeleteAccount = true
                }
            } footer: {
                Text("Ștergerea contului este permanentă: nu vei mai putea autentifica cu acest email. Datele de business rămân arhivate pe server conform politicii.")
            }

            Section("Societate curentă") {
                TextField("Denumire", text: $companyName)
                TextField("CUI", text: $companyCui)
                if !promoCode.isEmpty {
                    HStack {
                        VStack(alignment: .leading, spacing: 2) {
                            Text("Cod promoțional").font(.caption).foregroundStyle(.secondary)
                            Text(promoCode)
                                .font(.body.monospaced().weight(.semibold))
                                .foregroundStyle(AppTheme.deep)
                        }
                        Spacer()
                        Button(copiedPromo ? "Copiat!" : "Copiază") {
                            UIPasteboard.general.string = promoCode
                            copiedPromo = true
                            Task {
                                try? await Task.sleep(for: .seconds(1.5))
                                copiedPromo = false
                            }
                        }
                        .buttonStyle(.bordered)
                    }
                }
                TextField("Email", text: $companyEmail)
                TextField("Telefon", text: $companyPhone)
                TextField("Note factură", text: $invoiceNotes, axis: .vertical)
                if auth.can("settings_manage") {
                    Button("Salvează societatea") { Task { await saveCompany() } }
                }
                if !promoCode.isEmpty {
                    Button {
                        showReferralMail = true
                    } label: {
                        Label("Trimite mail recomandare", systemImage: "envelope.fill")
                    }
                }
                Button("Societate nouă") { showCreateCompany = true }
            }

            Section("Documente") {
                if auth.can("efactura_view") || auth.can("efactura_manage") {
                    NavigationLink {
                        EfacturaView()
                    } label: {
                        Label("e-Factura ANAF", systemImage: "antenna.radiowaves.left.and.right")
                    }
                }
                NavigationLink {
                    SeriesSettingsView()
                } label: {
                    Label("Serii", systemImage: "number")
                }
                NavigationLink {
                    PdfPersonalizeSettingsView()
                } label: {
                    Label("Personalizare PDF", systemImage: "paintbrush")
                }
                NavigationLink {
                    VatSettingsView()
                } label: {
                    Label("Cote TVA", systemImage: "percent")
                }
                NavigationLink {
                    LanguagesSettingsView()
                } label: {
                    Label("Limbi", systemImage: "globe")
                }
            }

            Section {
                LabeledContent("Stare", value: sync.status.label)
                LabeledContent("În așteptare", value: "\(sync.pendingCount)")
                Button("Sincronizează acum") { Task { await sync.syncNow() } }
            } header: {
                Text("Sincronizare")
            } footer: {
                Text("Când părăsești aplicația (butonul Home), sync-ul continuă scurt în fundal până se trimit datele în așteptare. Dacă forțezi închiderea din app switcher, iOS poate opri procesul.")
            }

            if auth.user?.canManageCompanyUsers == true {
                Section("Utilizatori") {
                    ForEach(users) { user in
                        VStack(alignment: .leading, spacing: 2) {
                            Text(user.name).fontWeight(.medium)
                            Text(user.email).font(.caption).foregroundStyle(.secondary)
                            if let memberships = user.memberships {
                                Text(memberships.map { "\($0.companyName ?? "") (\($0.role ?? ""))" }.joined(separator: ", "))
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                    NavigationLink("Adaugă utilizator") {
                        CreateCompanyUserView {
                            Task { await loadUsers() }
                        }
                    }
                }
            }

            if let message {
                Section { Text(message).foregroundStyle(AppTheme.teal) }
            }
        }
        .navigationTitle("Setări")
        .task {
            profileName = auth.user?.name ?? ""
            loadCompanyFields()
            await loadCompanyDetails()
            await loadUsers()
            await loadProfile()
            await subscription.start()
        }
        .sheet(isPresented: $showCreateCompany) {
            NavigationStack { CreateCompanyView() }
        }
        .sheet(isPresented: $showReferralMail) {
            NavigationStack {
                ReferralRecommendMailView(
                    companyId: auth.currentCompanyId ?? 0,
                    companyName: companyName,
                    promoCode: promoCode
                )
            }
        }
        .alert("Șterge contul?", isPresented: $showDeleteAccount) {
            SecureField("Parola contului", text: $deletePassword)
            Button("Anulează", role: .cancel) {
                deletePassword = ""
            }
            Button("Șterge definitiv", role: .destructive) {
                Task {
                    deleteInFlight = true
                    defer { deleteInFlight = false }
                    let ok = await auth.deleteAccount(password: deletePassword)
                    deletePassword = ""
                    if !ok, let err = auth.errorMessage {
                        message = err
                    }
                }
            }
            .disabled(deletePassword.isEmpty || deleteInFlight)
        } message: {
            Text("Introdu parola pentru a confirma. Acțiunea este ireversibilă din perspectiva autentificării.")
        }
    }

    private func loadCompanyFields() {
        guard let c = auth.currentCompany else { return }
        companyName = c.name
        companyCui = c.cui ?? ""
        companyEmail = c.email ?? ""
        companyPhone = c.phone ?? ""
        promoCode = c.promoCode ?? ""
    }

    private func loadCompanyDetails() async {
        guard let id = auth.currentCompanyId else { return }
        do {
            let response: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "GET", path: "companies/\(id)"
            )
            companyName = response.data.name
            companyCui = response.data.cui ?? ""
            companyEmail = response.data.email ?? ""
            companyPhone = response.data.phone ?? ""
            invoiceNotes = response.data.invoiceNotes ?? ""
            promoCode = response.data.promoCode ?? promoCode
        } catch {
            // keep cached
        }
    }

    private func loadUsers() async {
        guard auth.user?.canManageCompanyUsers == true else { return }
        do {
            struct UsersResponse: Decodable {
                let data: [CompanyUserRow]
            }
            let response: UsersResponse = try await APIClient.shared.request("GET", path: "company-users")
            users = response.data
        } catch {
            // ignore
        }
    }

    private func loadProfile() async {
        struct ProfileResponse: Decodable {
            let user: APIUser
        }
        if let response: ProfileResponse = try? await APIClient.shared.request("GET", path: "profile") {
            profileName = response.user.name
        }
    }

    private func saveProfile() async {
        struct Body: Encodable { let name: String }
        struct Resp: Decodable { let user: APIUser }
        do {
            let _: Resp = try await APIClient.shared.request(
                "PUT", path: "profile", body: Body(name: profileName)
            )
            message = "Profil salvat."
            await auth.refreshMe()
        } catch {
            message = error.localizedDescription
        }
    }

    private func saveCompany() async {
        guard let id = auth.currentCompanyId else { return }
        struct Body: Encodable {
            let name: String
            let cui: String
            let email: String
            let phone: String
            let invoiceNotes: String
            enum CodingKeys: String, CodingKey {
                case name, cui, email, phone
                case invoiceNotes = "invoice_notes"
            }
        }
        do {
            let _: DataEnvelope<APICompany> = try await APIClient.shared.request(
                "PUT",
                path: "companies/\(id)",
                body: Body(
                    name: companyName,
                    cui: companyCui,
                    email: companyEmail,
                    phone: companyPhone,
                    invoiceNotes: invoiceNotes
                )
            )
            message = "Societate actualizată."
            await auth.refreshMe()
        } catch {
            message = error.localizedDescription
        }
    }
}

struct APICompanyDetailed: Decodable {
    let id: Int
    let name: String
    let cui: String?
    let email: String?
    let phone: String?
    let invoiceNotes: String?
    let promoCode: String?
    let vatPayer: Bool?
    let vatOnCollection: Bool?
    let defaultVatRate: Double?
    let invoiceColor: String?
    let invoiceTemplate: String?
    let documentLanguages: [String]?

    enum CodingKeys: String, CodingKey {
        case id, name, cui, email, phone
        case invoiceNotes = "invoice_notes"
        case promoCode = "promo_code"
        case vatPayer = "vat_payer"
        case vatOnCollection = "vat_on_collection"
        case defaultVatRate = "default_vat_rate"
        case invoiceColor = "invoice_color"
        case invoiceTemplate = "invoice_template"
        case documentLanguages = "document_languages"
    }
}

struct ReferralRecommendMailView: View {
    let companyId: Int
    let companyName: String
    let promoCode: String
    @Environment(\.dismiss) private var dismiss
    @State private var emails = ""
    @State private var message: String?
    @State private var isError = false
    @State private var sending = false

    var body: some View {
        Form {
            Section {
                LabeledContent("Societate", value: companyName)
                LabeledContent("Cod promo", value: promoCode)
            } footer: {
                Text("Destinatarii primesc invitația cu codul tău promoțional (max. 10 adrese).")
            }

            Section("Adrese email") {
                TextField("ex: coleg@firma.ro, prieten@email.com", text: $emails, axis: .vertical)
                    .lineLimit(3...6)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.emailAddress)
                    .autocorrectionDisabled()
            }

            if let message {
                Section {
                    Text(message).foregroundStyle(isError ? .red : AppTheme.teal)
                }
            }

            Section {
                Button {
                    Task { await send() }
                } label: {
                    if sending {
                        ProgressView()
                    } else {
                        Text("Trimite mail recomandare")
                    }
                }
                .disabled(sending || emails.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
        }
        .navigationTitle("Recomandare")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItem(placement: .cancellationAction) {
                Button("Închide") { dismiss() }
            }
        }
    }

    private func send() async {
        sending = true
        isError = false
        message = nil
        defer { sending = false }
        do {
            struct Resp: Decodable { let message: String? }
            let response: Resp = try await APIClient.shared.request(
                "POST",
                path: "companies/\(companyId)/referral-recommend",
                body: ["emails": emails]
            )
            message = response.message ?? "Mail trimis."
            isError = false
        } catch {
            message = error.localizedDescription
            isError = true
        }
    }
}

struct CreateCompanyView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.dismiss) private var dismiss
    @State private var name = ""
    @State private var cui = ""
    @State private var address = ""
    @State private var city = ""
    @State private var county = ""
    @State private var error: String?
    @State private var loading = false

    var body: some View {
        Form {
            Section("Societate nouă") {
                TextField("Denumire", text: $name)
                TextField("CUI", text: $cui)
                Button("Preluare ANAF") { Task { await anaf() } }
                TextField("Adresă", text: $address)
                TextField("Oraș", text: $city)
                TextField("Județ", text: $county)
            }
            if let error {
                Section { Text(error).foregroundStyle(.red) }
            }
            Section {
                Button(loading ? "Se creează…" : "Creează societatea") {
                    Task { await create() }
                }
                .disabled(name.isEmpty || loading)
            }
        }
        .navigationTitle("Societate")
        .toolbar {
            if !auth.companies.isEmpty {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Închide") { dismiss() }
                }
            }
        }
    }

    private func anaf() async {
        do {
            let data = try await APIClient.shared.rawRequest(
                "POST", path: "companies/anaf-lookup", body: ["cui": cui]
            )
            if let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
               let payload = json["data"] as? [String: Any] {
                name = (payload["name"] as? String) ?? name
                address = (payload["address"] as? String) ?? address
                city = (payload["city"] as? String) ?? city
                county = (payload["county"] as? String) ?? county
            }
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func create() async {
        loading = true
        defer { loading = false }
        struct Body: Encodable {
            let name: String
            let cui: String
            let address: String
            let city: String
            let county: String
        }
        do {
            let response: DataEnvelope<APICompany> = try await APIClient.shared.request(
                "POST",
                path: "companies",
                body: Body(name: name, cui: cui, address: address, city: city, county: county)
            )
            await auth.refreshMe()
            await auth.switchCompany(response.data)
            await sync.syncNow()
            dismiss()
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct CreateCompanyUserView: View {
    @Environment(\.dismiss) private var dismiss
    var onSaved: () -> Void

    @State private var name = ""
    @State private var email = ""
    @State private var password = ""
    @State private var error: String?
    @Environment(AuthStore.self) private var auth

    var body: some View {
        Form {
            Section {
                TextField("Nume", text: $name)
                TextField("Email", text: $email)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.emailAddress)
                SecureField("Parolă", text: $password)
            }
            if let error {
                Section { Text(error).foregroundStyle(.red) }
            }
        }
        .navigationTitle("Utilizator nou")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("Salvează") { Task { await save() } }
            }
        }
    }

    private func save() async {
        guard let companyId = auth.currentCompanyId else { return }
        struct Body: Encodable {
            let name: String
            let email: String
            let password: String
            let companyIds: [Int]
            enum CodingKeys: String, CodingKey {
                case name, email, password
                case companyIds = "company_ids"
            }
        }
        do {
            let _: DataEnvelope<APIUserMini> = try await APIClient.shared.request(
                "POST",
                path: "company-users",
                body: Body(name: name, email: email, password: password, companyIds: [companyId])
            )
            onSaved()
            dismiss()
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct APIUserMini: Decodable {
    let id: Int
    let name: String
    let email: String
}
