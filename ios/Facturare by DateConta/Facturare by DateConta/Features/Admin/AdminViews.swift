import SwiftUI

struct AdminHubView: View {
    var body: some View {
        List {
            Section {
                NavigationLink {
                    AdminStatsView()
                } label: {
                    Label("Statistici", systemImage: "chart.bar.doc.horizontal.fill")
                }

                NavigationLink {
                    AdminCompaniesView()
                } label: {
                    Label("Societăți & promoții", systemImage: "building.2.fill")
                }

                NavigationLink {
                    AdminPromoMailView()
                } label: {
                    Label("Trimite mail reclamă", systemImage: "megaphone.fill")
                }
            } header: {
                Text("Administrator")
            } footer: {
                Text("Mail reclamă: de la Razvan Ivan — FLY DAVID SRL, fără cod promo (max. 20 adrese).")
            }

            Section("Pe web") {
                Button {
                    Task { await WebSession.open(path: "/admin/comenzi") }
                } label: {
                    Label("Comenzi OP / abonament", systemImage: "creditcard.fill")
                }
                Button {
                    Task { await WebSession.open(path: "/admin/integrari/netopia") }
                } label: {
                    Label("Integrări plăți", systemImage: "link")
                }
            }
        }
        .navigationTitle("Admin")
    }
}

struct AdminPromoMailView: View {
    @State private var emails = ""
    @State private var message: String?
    @State private var isError = false
    @State private var sending = false

    var body: some View {
        Form {
            Section {
                Text("De la Razvan Ivan — FLY DAVID SRL, fără cod promoțional.")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }

            Section {
                TextField("ex: coleg@firma.ro, prieten@email.com", text: $emails, axis: .vertical)
                    .lineLimit(4...8)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.emailAddress)
                    .autocorrectionDisabled()
            } header: {
                Text("Adrese email")
            } footer: {
                Text("Separă adresele prin virgulă, spațiu sau linie nouă (maximum 20).")
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
                        Text("Trimite mail reclamă")
                    }
                }
                .disabled(sending || emails.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
        }
        .navigationTitle("Mail reclamă")
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
                path: "admin/promo-mail",
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

struct AdminStatsView: View {
    @Environment(\.horizontalSizeClass) private var sizeClass
    @State private var stats: AdminStatsData?
    @State private var error: String?
    @State private var loading = false

    private var columns: [GridItem] {
        let count = sizeClass == .regular ? 4 : 2
        return Array(repeating: GridItem(.flexible(), spacing: 12), count: count)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                if let error {
                    Text(error).foregroundStyle(.red)
                }

                if let stats {
                    Text("Vizitatori").font(.title2.bold()).foregroundStyle(AppTheme.deep)
                    LazyVGrid(columns: columns, spacing: 12) {
                        AdminStatCard(
                            title: "Total de la lansare",
                            value: "\(formatInt(stats.visitors.all.unique)) / \(formatInt(stats.visitors.all.total))",
                            caption: "unici / vizualizări"
                        )
                        AdminStatCard(
                            title: "Ultima lună",
                            value: "\(formatInt(stats.visitors.month.unique)) / \(formatInt(stats.visitors.month.total))",
                            caption: "30 zile"
                        )
                        AdminStatCard(
                            title: "Ultima săptămână",
                            value: "\(formatInt(stats.visitors.week.unique)) / \(formatInt(stats.visitors.week.total))",
                            caption: "7 zile"
                        )
                        AdminStatCard(
                            title: "Activi acum",
                            value: "\(formatInt(stats.visitors.active.unique))",
                            caption: "\(formatInt(stats.totals.usersLogged)) logați",
                            emphasize: true
                        )
                    }

                    Text("Platformă").font(.title2.bold()).foregroundStyle(AppTheme.deep)
                    LazyVGrid(columns: columns, spacing: 12) {
                        AdminStatCard(title: "Utilizatori", value: formatInt(stats.totals.users), caption: "+\(formatInt(stats.totals.usersWeek)) săptămâna asta")
                        AdminStatCard(title: "Societăți", value: formatInt(stats.totals.companies))
                        AdminStatCard(title: "Facturi emise", value: formatInt(stats.totals.invoicesIssued))
                        AdminStatCard(title: "Facturi lună", value: formatInt(stats.totals.invoicesMonth))
                        AdminStatCard(title: "Valoare lună", value: formatMoney(stats.totals.invoiceTotalMonth))
                        AdminStatCard(title: "Încasări lună", value: formatMoney(stats.totals.paymentsMonth))
                        AdminStatCard(title: "Clienți operator", value: formatInt(stats.totals.clients))
                    }

                    if !stats.activeVisitors.isEmpty {
                        Text("Activi acum").font(.headline).foregroundStyle(AppTheme.deep)
                        ForEach(stats.activeVisitors) { visitor in
                            VStack(alignment: .leading, spacing: 4) {
                                Text(visitor.userEmail ?? "anonim").fontWeight(.semibold)
                                Text([visitor.country ?? visitor.countryCode, visitor.browser, visitor.os]
                                    .compactMap { $0 }
                                    .filter { !$0.isEmpty }
                                    .joined(separator: " · "))
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                if let path = visitor.path {
                                    Text(path).font(.caption2).foregroundStyle(.secondary)
                                }
                            }
                            .padding()
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(AppTheme.mist, in: RoundedRectangle(cornerRadius: 12))
                        }
                    }

                    if !stats.topCountries.isEmpty {
                        Text("Țări").font(.headline).foregroundStyle(AppTheme.deep)
                        ForEach(stats.topCountries) { row in
                            HStack {
                                Text(row.name ?? row.code ?? "—")
                                Spacer()
                                Text("\(row.visitors)").foregroundStyle(.secondary)
                            }
                        }
                    }

                    if !stats.users.isEmpty {
                        Text("Utilizatori recenti").font(.headline).foregroundStyle(AppTheme.deep)
                        ForEach(stats.users) { user in
                            VStack(alignment: .leading, spacing: 4) {
                                HStack {
                                    Text(user.name).fontWeight(.semibold)
                                    if user.isAdmin == true {
                                        Text("ADMIN")
                                            .font(.caption2.bold())
                                            .padding(.horizontal, 6)
                                            .padding(.vertical, 2)
                                            .background(AppTheme.warm.opacity(0.2), in: Capsule())
                                            .foregroundStyle(AppTheme.warm)
                                    }
                                    if user.isLoggedNow == true {
                                        Circle().fill(.green).frame(width: 8, height: 8)
                                    }
                                    Spacer()
                                }
                                Text(user.email).font(.caption).foregroundStyle(.secondary)
                                Text([user.accessLabel, user.plan]
                                    .compactMap { $0 }
                                    .filter { !$0.isEmpty }
                                    .joined(separator: " · "))
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                            .padding()
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(.white, in: RoundedRectangle(cornerRadius: 12))
                            .shadow(color: AppTheme.deep.opacity(0.05), radius: 6, y: 2)
                        }
                    }
                } else if loading {
                    ProgressView("Se încarcă statisticile…")
                        .frame(maxWidth: .infinity, minHeight: 200)
                }
            }
            .padding()
        }
        .background(AppTheme.mist.opacity(0.35).ignoresSafeArea())
        .navigationTitle("Statistici")
        .refreshable { await load() }
        .task { await load() }
        .toolbar {
            ToolbarItem(placement: .primaryAction) {
                Button {
                    Task { await load() }
                } label: {
                    Image(systemName: "arrow.clockwise")
                }
                .disabled(loading)
            }
        }
    }

    private func load() async {
        loading = true
        error = nil
        defer { loading = false }
        do {
            let response: AdminStatsResponse = try await APIClient.shared.request("GET", path: "admin/stats")
            stats = response.data
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func formatInt(_ value: Int) -> String {
        let f = NumberFormatter()
        f.numberStyle = .decimal
        return f.string(from: NSNumber(value: value)) ?? "\(value)"
    }

    private func formatMoney(_ value: Double) -> String {
        let f = NumberFormatter()
        f.numberStyle = .currency
        f.currencyCode = "RON"
        f.maximumFractionDigits = 0
        return f.string(from: NSNumber(value: value)) ?? "\(value)"
    }
}

struct AdminCompaniesView: View {
    @State private var companies: [AdminCompanyRow] = []
    @State private var query = ""
    @State private var error: String?
    @State private var loading = false

    var body: some View {
        List {
            if let error {
                Text(error).foregroundStyle(.red)
            }
            ForEach(companies) { company in
                VStack(alignment: .leading, spacing: 4) {
                    Text(company.name).fontWeight(.semibold)
                    Text([company.cui, company.promoCode].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " · "))
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Text(company.ownerEmail ?? company.ownerName ?? "—")
                        .font(.caption)
                    Text(company.accessLabel ?? "—")
                        .font(.caption2)
                        .foregroundStyle(AppTheme.teal)
                }
                .padding(.vertical, 2)
            }
        }
        .navigationTitle("Societăți")
        .searchable(text: $query, prompt: "Caută firmă, CUI, email…")
        .onSubmit(of: .search) {
            Task { await load() }
        }
        .onChange(of: query) { _, newValue in
            if newValue.isEmpty {
                Task { await load() }
            }
        }
        .refreshable { await load() }
        .task { await load() }
        .overlay {
            if loading && companies.isEmpty {
                ProgressView()
            }
        }
    }

    private func load() async {
        loading = true
        error = nil
        defer { loading = false }
        do {
            var queryItems: [URLQueryItem] = []
            let q = query.trimmingCharacters(in: .whitespacesAndNewlines)
            if !q.isEmpty {
                queryItems.append(URLQueryItem(name: "q", value: q))
            }
            let response: DataEnvelope<[AdminCompanyRow]> = try await APIClient.shared.request(
                "GET",
                path: "admin/companies",
                query: queryItems
            )
            companies = response.data
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private struct AdminStatCard: View {
    let title: String
    let value: String
    var caption: String? = nil
    var emphasize = false

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(emphasize ? AppTheme.teal : .secondary)
            Text(value)
                .font(.title3.bold())
                .foregroundStyle(emphasize ? AppTheme.teal : AppTheme.deep)
                .minimumScaleFactor(0.7)
                .lineLimit(2)
            if let caption {
                Text(caption).font(.caption2).foregroundStyle(.secondary)
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(
            RoundedRectangle(cornerRadius: 14)
                .fill(emphasize ? AppTheme.teal.opacity(0.12) : .white)
        )
        .shadow(color: AppTheme.deep.opacity(0.06), radius: 8, y: 2)
    }
}
