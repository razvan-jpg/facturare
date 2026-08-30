import SwiftUI
import SwiftData

/// Secțiuni aliniate cu meniul web (`config/nav.php` + Acasă).
enum AppSection: String, CaseIterable, Identifiable, Hashable {
    case home, emite, liste, catalog, reports, help, legal, settings, admin

    var id: String { rawValue }

    var title: String {
        switch self {
        case .home: return "Acasă"
        case .emite: return "Emite"
        case .liste: return "Liste"
        case .catalog: return "Catalog"
        case .reports: return "Rapoarte"
        case .help: return "Ajutor"
        case .legal: return "Legal"
        case .settings: return "Setări"
        case .admin: return "Admin"
        }
    }

    var icon: String {
        switch self {
        case .home: return "house.fill"
        case .emite: return "plus.square.fill"
        case .liste: return "list.bullet.rectangle.fill"
        case .catalog: return "square.grid.2x2.fill"
        case .reports: return "chart.bar.fill"
        case .help: return "questionmark.circle.fill"
        case .legal: return "building.columns.fill"
        case .settings: return "gearshape.fill"
        case .admin: return "shield.lefthalf.filled"
        }
    }

    /// Tabs principale pe iPhone (restul în „Mai mult”), ca pe web: Acasă → Emite → Liste → Catalog.
    static let primaryTabs: [AppSection] = [
        .home, .emite, .liste, .catalog,
    ]
}

private enum CompactTab: Hashable {
    case section(AppSection)
    case more
}

struct RootShellView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Environment(\.horizontalSizeClass) private var sizeClass

    @State private var section: AppSection = .home
    @State private var detailPath = NavigationPath()
    /// Incrementat la fiecare tap în sidebar — forțează recrearea coloanei detail.
    @State private var detailEpoch = 0
    @State private var compactTab: CompactTab = .section(.home)
    @State private var morePath = NavigationPath()

    var body: some View {
        Group {
            if auth.companies.isEmpty && !auth.isAdmin {
                NavigationStack {
                    CreateCompanyView()
                }
            } else if auth.companies.isEmpty && auth.isAdmin {
                NavigationStack {
                    AdminHubView()
                        .toolbar {
                            ToolbarItem(placement: .primaryAction) {
                                NavigationLink("Firmă nouă") {
                                    CreateCompanyView()
                                }
                            }
                        }
                }
            } else if sizeClass == .regular {
                regularShell
            } else {
                compactShell
            }
        }
        .task {
            sync.attach(context: modelContext)
            await auth.refreshMe()
            if auth.currentCompanyId != nil {
                await sync.syncNow()
            }
        }
#if os(iOS)
        .onReceive(NotificationCenter.default.publisher(for: UIApplication.willEnterForegroundNotification)) { _ in
            Task {
                if auth.currentCompanyId != nil {
                    await sync.syncNow()
                }
            }
        }
#endif
    }

    // MARK: - iPad / wide
    // HStack propriu (nu NavigationSplitView): pe simulator iPad selecția din sidebar
    // nu actualiza detaliul; butoanele + `.id` pe stack schimbă ecranul imediat.

    private var regularShell: some View {
        HStack(spacing: 0) {
            regularSidebar
                .frame(width: 280)
                .frame(maxHeight: .infinity)
                .background(Color(.secondarySystemBackground))

            Divider()

            VStack(spacing: 0) {
                SyncKeepOpenBanner()
                    .padding(.horizontal, 16)
                    .padding(.vertical, 10)
                    .id("sync-banner-\(detailEpoch)-\(section.rawValue)")

                NavigationStack(path: $detailPath) {
                    detailView(for: section)
                        .toolbar {
                            ToolbarItem(placement: .primaryAction) {
                                syncBadge
                            }
                        }
                }
                .id("detail-\(companyContentID)-\(section.rawValue)-\(detailEpoch)")
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity)
        }
        .ignoresSafeArea(.keyboard, edges: .bottom)
    }

    private var regularSidebar: some View {
        VStack(spacing: 0) {
            // Brand mark ca pe web (`dc-topnav-brand`): logo + DateConta / Facturare
            HStack(spacing: 10) {
                Image("BrandLogo")
                    .resizable()
                    .scaledToFill()
                    .frame(width: 40, height: 40)
                    .clipShape(RoundedRectangle(cornerRadius: 8, style: .continuous))
                    .overlay(
                        RoundedRectangle(cornerRadius: 8, style: .continuous)
                            .strokeBorder(Color.primary.opacity(0.08), lineWidth: 1)
                    )

                VStack(alignment: .leading, spacing: 1) {
                    Text("DateConta")
                        .font(.system(.body, design: .serif).weight(.bold))
                        .foregroundStyle(AppTheme.deep)
                    Text("Facturare")
                        .font(.system(size: 10, weight: .semibold))
                        .foregroundStyle(.secondary)
                        .textCase(.uppercase)
                        .tracking(1.4)
                }
            }
            .accessibilityElement(children: .ignore)
            .accessibilityLabel("DateConta Facturare")
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(.horizontal, 20)
            .padding(.top, 20)
            .padding(.bottom, 8)

            List {
                companySection

                Section("Meniu") {
                    ForEach(visibleSections.filter { $0 != .admin && $0 != .settings }) { item in
                        sidebarButton(item)
                    }
                }

                if auth.isAdmin {
                    Section("Administrator") {
                        sidebarButton(.admin)
                    }
                }

                Section {
                    sidebarButton(.settings)
                    Button(role: .destructive) {
                        Task { await auth.logout() }
                    } label: {
                        Label("Ieși din cont", systemImage: "rectangle.portrait.and.arrow.right")
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .contentShape(Rectangle())
                    }
                    .buttonStyle(.plain)
                } footer: {
                    if let email = auth.user?.email {
                        Text(email)
                    }
                }
            }
            .listStyle(.sidebar)
            .scrollContentBackground(.hidden)

            Divider()

            appVersionsFooter
                .padding(.horizontal, 12)
                .padding(.vertical, 14)
        }
    }

    private func selectSection(_ item: AppSection) {
        section = item
        detailPath = NavigationPath()
        detailEpoch += 1
    }

    private func sidebarButton(_ item: AppSection) -> some View {
        Button {
            selectSection(item)
        } label: {
            Label(item.title, systemImage: item.icon)
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding(.vertical, 4)
                .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .listRowBackground(
            RoundedRectangle(cornerRadius: 8)
                .fill(section == item ? AppTheme.accent.opacity(0.18) : Color.clear)
        )
        .foregroundStyle(section == item ? AppTheme.deep : .primary)
    }

    // MARK: - iPhone

    private var compactShell: some View {
        VStack(spacing: 0) {
            SyncKeepOpenBanner()
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .id("sync-banner-compact-\(String(describing: compactTab))-\(companyContentID)")

            TabView(selection: $compactTab) {
            ForEach(visiblePrimaryTabs) { item in
                NavigationStack {
                    detailView(for: item)
                        .toolbar {
                            ToolbarItem(placement: .primaryAction) {
                                syncBadge
                            }
                        }
                }
                // Reset stack la schimbarea firmei (altfel rămâi pe Facturi goale).
                .id("tab-\(item.rawValue)-\(companyContentID)")
                .tabItem { Label(item.title, systemImage: item.icon) }
                .tag(CompactTab.section(item))
            }

            if !visibleMoreSections.isEmpty {
                NavigationStack(path: $morePath) {
                    List {
                        companySection

                        Section("Meniu") {
                            ForEach(visibleMoreSections.filter { $0 != .admin && $0 != .settings }) { item in
                                Button {
                                    openMoreSection(item)
                                } label: {
                                    Label(item.title, systemImage: item.icon)
                                        .frame(maxWidth: .infinity, alignment: .leading)
                                        .contentShape(Rectangle())
                                }
                                .buttonStyle(.plain)
                            }
                        }

                        if auth.isAdmin {
                            Section("Administrator") {
                                Button {
                                    openMoreSection(.admin)
                                } label: {
                                    Label(AppSection.admin.title, systemImage: AppSection.admin.icon)
                                        .frame(maxWidth: .infinity, alignment: .leading)
                                        .contentShape(Rectangle())
                                }
                                .buttonStyle(.plain)
                            }
                        }

                        Section {
                            Button {
                                openMoreSection(.settings)
                            } label: {
                                Label(AppSection.settings.title, systemImage: AppSection.settings.icon)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .contentShape(Rectangle())
                            }
                            .buttonStyle(.plain)
                            Button(role: .destructive) {
                                Task { await auth.logout() }
                            } label: {
                                Label("Ieși din cont", systemImage: "rectangle.portrait.and.arrow.right")
                            }
                        } footer: {
                            if let email = auth.user?.email {
                                Text(email)
                            }
                        }
                    }
                    .listStyle(.insetGrouped)
                    .navigationTitle("Mai mult")
                    .safeAreaInset(edge: .bottom, spacing: 0) {
                        appVersionsFooter
                            .padding(.horizontal, 12)
                            .padding(.vertical, 14)
                            .frame(maxWidth: .infinity)
                            .background(.bar)
                    }
                    .navigationDestination(for: AppSection.self) { item in
                        detailView(for: item)
                            .toolbar {
                                ToolbarItem(placement: .primaryAction) {
                                    syncBadge
                                }
                            }
                    }
                    .toolbar {
                        ToolbarItem(placement: .primaryAction) {
                            syncBadge
                        }
                    }
                }
                .id("more-\(companyContentID)")
                .tabItem { Label("Mai mult", systemImage: "ellipsis.circle.fill") }
                .tag(CompactTab.more)
            }
            } // TabView
        } // VStack + banner
    }

    /// Înlocuiește stack-ul (nu adaugă peste Rapoarte/Setări etc.).
    private func openMoreSection(_ item: AppSection) {
        morePath = NavigationPath()
        morePath.append(item)
    }

    /// Schimbă identitatea view-urilor ca `.task` / state local să se reîncarce la altă firmă.
    private var companyContentID: Int {
        auth.currentCompanyId ?? 0
    }

    // MARK: - Shared

    @ViewBuilder
    private var companySection: some View {
        if let company = auth.currentCompany {
            Section("Societate") {
                Menu {
                    ForEach(auth.companies) { c in
                        Button {
                            Task { await switchToCompany(c) }
                        } label: {
                            if c.id == auth.currentCompanyId {
                                Label(c.name, systemImage: "checkmark")
                            } else {
                                Text(c.name)
                            }
                        }
                    }
                } label: {
                    Label(company.name, systemImage: "building.2")
                }
            }
        }
    }

    private var syncBadge: some View {
        SyncStatusBadge(status: sync.status, pending: sync.pendingCount)
            .onTapGesture {
                Task { await sync.syncNow() }
            }
    }

    private var appVersionsFooter: some View {
        VStack(alignment: .center, spacing: 4) {
            Text("Versiune iOS \(AppVersion.ios)")
                .foregroundStyle(Color.blue)
            Text("Versiune Web \(auth.webAppVersion ?? "—")")
                .foregroundStyle(Color.green)
        }
        .font(.system(size: 12, weight: .bold))
        .multilineTextAlignment(.center)
        .frame(maxWidth: .infinity, alignment: .center)
    }

    private func switchToCompany(_ company: APICompany) async {
        guard company.id != auth.currentCompanyId else { return }
        morePath = NavigationPath()
        await auth.switchCompany(company)
        if auth.currentCompanyId == company.id {
            await sync.syncNow()
        }
    }

    private var visibleSections: [AppSection] {
        AppSection.allCases.filter(isVisible)
    }

    private var visiblePrimaryTabs: [AppSection] {
        AppSection.primaryTabs.filter(isVisible)
    }

    private var visibleMoreSections: [AppSection] {
        visibleSections.filter { !AppSection.primaryTabs.contains($0) }
    }

    private func isVisible(_ section: AppSection) -> Bool {
        switch section {
        case .home, .settings, .help, .legal:
            return !auth.companies.isEmpty
        case .admin:
            return auth.isAdmin
        case .emite:
            return auth.can("documents_manage")
                || auth.can("payments_manage")
                || auth.can("recurring_manage")
                || auth.can("payments_view")
        case .liste:
            return auth.can("documents_view")
                || auth.can("documents_manage")
                || auth.can("recurring_view")
                || auth.can("recurring_manage")
        case .catalog:
            return auth.can("clients_view")
                || auth.can("clients_manage")
                || auth.can("products_view")
                || auth.can("products_manage")
        case .reports:
            return auth.can("reports_view") || auth.can("reports_manage")
        }
    }

    @ViewBuilder
    private func detailView(for section: AppSection) -> some View {
        switch section {
        case .home: DashboardView()
        case .emite: EmiteHubView()
        case .liste: ListeHubView()
        case .catalog: CatalogHubView()
        case .reports: ReportsHubView()
        case .help: HelpHubView()
        case .legal: LegalHubView()
        case .settings: SettingsView()
        case .admin: AdminHubView()
        }
    }
}
