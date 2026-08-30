import SwiftUI
import SwiftData

struct DashboardView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(\.horizontalSizeClass) private var sizeClass
    @Query(sort: \LocalDocument.updatedAt, order: .reverse) private var localDocuments: [LocalDocument]
    @State private var dashboard: DashboardResponse?
    @State private var error: String?
    @State private var loading = false

    private var statsColumns: [GridItem] {
        let count = sizeClass == .regular ? 4 : 2
        return Array(repeating: GridItem(.flexible(), spacing: 12), count: count)
    }

    private var paidColumns: [GridItem] {
        Array(repeating: GridItem(.flexible(), spacing: 12), count: sizeClass == .regular ? 2 : 1)
    }

    private var drafts: [DashboardDocRow] {
        dashboard?.drafts ?? dashboard?.recentDocuments ?? []
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                VStack(alignment: .leading, spacing: 4) {
                    Text(auth.currentCompany?.name ?? "Panou")
                        .font(.largeTitle.bold())
                        .foregroundStyle(AppTheme.deep)
                    if let label = dashboard?.accessLabel ?? auth.user?.accessLabel, !label.isEmpty {
                        Text(label)
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                }

                if let error {
                    Text(error).foregroundStyle(.red)
                }

                if let stats = dashboard?.stats {
                    LazyVGrid(columns: statsColumns, spacing: 12) {
                        StatCard(
                            title: "De încasat de la clienți",
                            value: formatMoney(stats.clientsReceivableToday ?? 0),
                            caption: "la data de azi",
                            emphasize: true
                        )
                        StatCard(
                            title: "Facturat azi",
                            value: formatMoney(stats.invoicesTodayTotal ?? 0)
                        )
                        StatCard(
                            title: "Facturat luna aceasta",
                            value: formatMoney(stats.invoicesMonthTotal)
                        )
                        StatCard(
                            title: "Neplătite / Draft-uri",
                            value: "\(stats.unpaidCount ?? dashboard?.unpaid.count ?? 0) / \(stats.draftsCount ?? drafts.count)"
                        )
                    }

                    LazyVGrid(columns: paidColumns, spacing: 12) {
                        StatCard(
                            title: "Încasat azi",
                            value: formatMoney(stats.paymentsTodayTotal ?? 0),
                            methodLines: methodCaptions(stats.paymentsTodayByMethod)
                        )
                        StatCard(
                            title: "Încasat luna aceasta",
                            value: formatMoney(stats.paymentsMonthTotal),
                            methodLines: methodCaptions(stats.paymentsMonthByMethod)
                        )
                    }
                }

                if sizeClass == .regular {
                    HStack(alignment: .top, spacing: 16) {
                        unpaidSection
                        draftsSection
                    }
                } else {
                    unpaidSection
                    draftsSection
                }
            }
            .padding()
        }
        .background(
            LinearGradient(colors: [.white, AppTheme.mist], startPoint: .top, endPoint: .bottom)
                .ignoresSafeArea()
        )
        .navigationTitle("Acasă")
        .refreshable { await load() }
        .task(id: auth.currentCompanyId) { await load() }
        .overlay { if loading && dashboard == nil { ProgressView() } }
    }

    @ViewBuilder
    private var unpaidSection: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("De încasat").font(.headline).foregroundStyle(AppTheme.deep)
            if let unpaid = dashboard?.unpaid, !unpaid.isEmpty {
                ForEach(unpaid) { row in
                    docRowCard(
                        title: row.displayTitle,
                        subtitle: row.clientName ?? "—",
                        trailing: formatMoney(row.remaining ?? row.total ?? 0),
                        trailingCaption: row.dueDate,
                        trailingColor: AppTheme.warm,
                        serverId: row.id
                    )
                }
            } else if dashboard != nil {
                Text("Nicio factură neplătită.")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                    .padding(.vertical, 8)
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    @ViewBuilder
    private var draftsSection: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Draft-uri recente").font(.headline).foregroundStyle(AppTheme.deep)
            if !drafts.isEmpty {
                ForEach(drafts) { doc in
                    docRowCard(
                        title: doc.typeLabel ?? doc.type ?? "Draft",
                        subtitle: doc.clientName ?? "—",
                        trailing: formatMoney(doc.total ?? 0),
                        trailingCaption: nil,
                        trailingColor: AppTheme.deep,
                        serverId: doc.id
                    )
                }
            } else if dashboard != nil {
                Text("Nu există draft-uri.")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                    .padding(.vertical, 8)
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    @ViewBuilder
    private func docRowCard(
        title: String,
        subtitle: String,
        trailing: String,
        trailingCaption: String?,
        trailingColor: Color,
        serverId: Int
    ) -> some View {
        let local = localDocument(serverId: serverId)
        Group {
            if let local {
                NavigationLink {
                    DocumentDetailView(document: local)
                } label: {
                    rowContent(title: title, subtitle: subtitle, trailing: trailing, trailingCaption: trailingCaption, trailingColor: trailingColor)
                }
                .buttonStyle(.plain)
            } else {
                rowContent(title: title, subtitle: subtitle, trailing: trailing, trailingCaption: trailingCaption, trailingColor: trailingColor)
            }
        }
    }

    private func rowContent(
        title: String,
        subtitle: String,
        trailing: String,
        trailingCaption: String?,
        trailingColor: Color
    ) -> some View {
        HStack {
            VStack(alignment: .leading, spacing: 2) {
                Text(title).fontWeight(.semibold).foregroundStyle(AppTheme.deep)
                Text(subtitle).font(.caption).foregroundStyle(.secondary)
            }
            Spacer()
            VStack(alignment: .trailing, spacing: 2) {
                Text(trailing).foregroundStyle(trailingColor).fontWeight(.semibold)
                if let trailingCaption {
                    Text(trailingCaption).font(.caption2).foregroundStyle(.secondary)
                }
            }
        }
        .padding()
        .background(AppTheme.mist, in: RoundedRectangle(cornerRadius: 12))
    }

    private func localDocument(serverId: Int) -> LocalDocument? {
        let companyId = auth.currentCompanyId
        return localDocuments.first {
            $0.serverId == serverId && !$0.isDeleted && (companyId == nil || $0.companyId == companyId)
        }
    }

    private func load() async {
        loading = true
        defer { loading = false }
        do {
            dashboard = try await APIClient.shared.request("GET", path: "dashboard")
            error = nil
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func formatMoney(_ value: Double) -> String {
        let f = NumberFormatter()
        f.numberStyle = .currency
        f.currencyCode = "RON"
        f.maximumFractionDigits = 2
        return f.string(from: NSNumber(value: value)) ?? "\(value)"
    }

    private func methodCaptions(_ breakdown: PaymentMethodBreakdown?) -> [String] {
        guard let breakdown else { return [] }
        return breakdown.lines.map { "\($0.0): \(formatMoney($0.1))" }
    }
}

private struct StatCard: View {
    let title: String
    let value: String
    var caption: String? = nil
    var methodLines: [String] = []
    var emphasize = false

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(emphasize ? AppTheme.teal : .secondary)
            Text(value)
                .font(.title3.bold())
                .foregroundStyle(emphasize ? AppTheme.teal : AppTheme.deep)
                .minimumScaleFactor(0.75)
                .lineLimit(2)
            if let caption {
                Text(caption).font(.caption2).foregroundStyle(.secondary)
            }
            if !methodLines.isEmpty {
                VStack(alignment: .leading, spacing: 2) {
                    ForEach(methodLines, id: \.self) { line in
                        Text(line)
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.top, 2)
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
