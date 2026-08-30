import SwiftUI
import SwiftData

private struct EmitDocType: Identifiable {
    let id: String
}

// MARK: - Emite (ca pe web)

struct EmiteHubView: View {
    @Environment(AuthStore.self) private var auth
    @Query(sort: \LocalDocument.updatedAt, order: .reverse) private var documents: [LocalDocument]
    @State private var createType: EmitDocType?
    @State private var showPayment = false
    @State private var showRecurring = false

    var body: some View {
        List {
            if auth.can("documents_manage") {
                Section("Documente noi") {
                    emitLink("Factură", type: "invoice")
                    emitLink("Proformă", type: "proforma")
                    emitLink("Aviz de însoțire", type: "delivery")
                    if auth.can("payments_manage") {
                        Button {
                            showPayment = true
                        } label: {
                            Label("Încasare", systemImage: "banknote")
                        }
                    }
                    NavigationLink {
                        DocumentsListView(initialFilter: "invoice")
                    } label: {
                        Label("Factură storno", systemImage: "arrow.uturn.backward")
                    }
                    NavigationLink {
                        DocumentsListView(initialFilter: "invoice")
                    } label: {
                        Label("Notă de creditare", systemImage: "doc.badge.minus")
                    }
                    if auth.can("recurring_manage") {
                        Button {
                            showRecurring = true
                        } label: {
                            Label("Factură recurentă", systemImage: "arrow.triangle.2.circlepath")
                        }
                    }
                }
            }

            if auth.can("payments_view") || auth.can("payments_manage") {
                Section("Bani") {
                    if auth.can("payments_manage") {
                        Button {
                            showPayment = true
                        } label: {
                            Label("Încasare nouă", systemImage: "plus.circle")
                        }
                    }
                    NavigationLink {
                        PaymentsListView()
                    } label: {
                        Label("Listă încasări", systemImage: "list.bullet.rectangle")
                    }
                }
            }
        }
        .navigationTitle("Emite")
        .sheet(item: $createType) { item in
            NavigationStack {
                DocumentEditorView(document: nil, initialType: item.id)
            }
        }
        .sheet(isPresented: $showPayment) {
            NavigationStack {
                PaymentCreateView(documents: issuedDocuments)
            }
        }
        .sheet(isPresented: $showRecurring) {
            NavigationStack {
                RecurringEditorView { }
            }
        }
    }

    private var issuedDocuments: [LocalDocument] {
        let companyId = auth.currentCompanyId ?? -1
        return documents.filter {
            !$0.isDeleted && $0.companyId == companyId && $0.status == "issued" && $0.serverId != nil
        }
    }

    private func emitLink(_ title: String, type: String) -> some View {
        Button {
            createType = EmitDocType(id: type)
        } label: {
            Label(title, systemImage: "doc.badge.plus")
        }
    }
}

// MARK: - Liste (ca pe web)

struct ListeHubView: View {
    @Environment(AuthStore.self) private var auth

    var body: some View {
        List {
            Section("Emise") {
                if auth.can("documents_view") || auth.can("documents_manage") {
                    listLink("Facturi", filter: "invoice")
                    listLink("Proforme", filter: "proforma")
                    listLink("Avize", filter: "delivery")
                    listLink("Chitanțe", filter: "receipt")
                    listLink("Facturi storno", filter: "storno")
                    listLink("Note de creditare", filter: "credit_note")
                }
                if auth.can("recurring_view") || auth.can("recurring_manage") {
                    NavigationLink {
                        RecurringListView()
                    } label: {
                        Label("Recurente", systemImage: "arrow.triangle.2.circlepath")
                    }
                }
            }
        }
        .navigationTitle("Liste")
    }

    private func listLink(_ title: String, filter: String) -> some View {
        NavigationLink {
            DocumentsListView(initialFilter: filter)
        } label: {
            Label(title, systemImage: "doc.text")
        }
    }
}

// MARK: - Catalog (ca pe web)

struct CatalogHubView: View {
    @Environment(AuthStore.self) private var auth

    var body: some View {
        List {
            if auth.can("clients_view") || auth.can("clients_manage") {
                NavigationLink {
                    ClientsListView()
                } label: {
                    Label("Clienți", systemImage: "person.2.fill")
                }
            }
            if auth.can("products_view") || auth.can("products_manage") {
                NavigationLink {
                    ProductsListView()
                } label: {
                    Label("Produse și servicii", systemImage: "shippingbox.fill")
                }
            }
        }
        .navigationTitle("Catalog")
    }
}

// MARK: - Rapoarte hub

struct ReportsHubView: View {
    @State private var exporting = false
    @State private var exportURL: URL?
    @State private var exportError: String?
    @State private var showShare = false

    var body: some View {
        List {
            NavigationLink {
                ReportsView()
            } label: {
                Label("Vânzări și încasări", systemImage: "chart.bar.fill")
            }
            NavigationLink {
                ReportsView(focusBalance: true)
            } label: {
                Label("Clienți (solduri)", systemImage: "person.crop.rectangle.stack")
            }
            Button {
                Task { await exportCSV() }
            } label: {
                Label(exporting ? "Se exportă…" : "Export CSV", systemImage: "square.and.arrow.up")
            }
            .disabled(exporting)
            if let exportError {
                Text(exportError).font(.caption).foregroundStyle(.red)
            }
        }
        .navigationTitle("Rapoarte")
        .sheet(isPresented: $showShare) {
            if let exportURL {
                NavigationStack {
                    ShareLink(item: exportURL) {
                        Label("Trimite CSV", systemImage: "square.and.arrow.up")
                    }
                    .navigationTitle("Export")
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) {
                            Button("Închide") { showShare = false }
                        }
                    }
                }
            }
        }
    }

    private func exportCSV() async {
        exporting = true
        exportError = nil
        defer { exporting = false }
        do {
            let from: String = {
                let f = DateFormatter()
                f.locale = Locale(identifier: "en_US_POSIX")
                f.dateFormat = "yyyy-MM-01"
                return f.string(from: Date())
            }()
            let to = DateFormats.today()
            let data = try await APIClient.shared.rawRequest(
                "GET",
                path: "reports/export",
                query: [
                    URLQueryItem(name: "from", value: from),
                    URLQueryItem(name: "to", value: to),
                ]
            )
            let url = FileManager.default.temporaryDirectory
                .appendingPathComponent("facturi-\(from)-\(to).csv")
            try data.write(to: url, options: .atomic)
            exportURL = url
            showShare = true
        } catch {
            exportError = error.localizedDescription
        }
    }
}
