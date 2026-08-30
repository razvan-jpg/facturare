import SwiftUI

struct RecurringListView: View {
    @Environment(AuthStore.self) private var auth
    @State private var items: [APIRecurring] = []
    @State private var error: String?
    @State private var showCreate = false

    var body: some View {
        List {
            if let error {
                Text(error).foregroundStyle(.red)
            }
            ForEach(items) { item in
                NavigationLink {
                    RecurringDetailView(item: item) {
                        Task { await load() }
                    }
                } label: {
                    VStack(alignment: .leading, spacing: 4) {
                        Text((item.title?.isEmpty == false ? item.title : nil) ?? item.clientName ?? "Abonament")
                            .fontWeight(.semibold)
                        Text(recurringDocLine(item))
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Text("\(item.frequency ?? "") · următoarea: \(item.nextRunDate ?? "—")")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Text(item.active == true ? "Activ" : "Pauzat")
                            .font(.caption2)
                            .foregroundStyle(item.active == true ? AppTheme.accent : .orange)
                    }
                }
            }
        }
        .navigationTitle("Recurente")
        .toolbar {
            if auth.can("recurring_manage") {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus") }
                }
            }
        }
        .refreshable { await load() }
        .task(id: auth.currentCompanyId) { await load() }
        .sheet(isPresented: $showCreate) {
            NavigationStack {
                RecurringEditorView {
                    Task { await load() }
                }
            }
        }
    }

    private func load() async {
        do {
            let response: DataEnvelope<[APIRecurring]> = try await APIClient.shared.request("GET", path: "recurring")
            items = response.data
            error = nil
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func recurringDocLine(_ item: APIRecurring) -> String {
        let typeLabel: String
        switch item.documentType {
        case "proforma": typeLabel = "Proformă"
        default: typeLabel = "Factură fiscală"
        }
        let series = (item.series?.isEmpty == false) ? item.series! : "serie implicită"
        return "\(typeLabel) · \(series)"
    }
}

struct RecurringDetailView: View {
    @Environment(AuthStore.self) private var auth
    let item: APIRecurring
    var onChanged: () -> Void
    @State private var message: String?

    var body: some View {
        List {
            Section {
                LabeledContent("Client", value: item.clientName ?? "—")
                LabeledContent("Tip document", value: item.documentType == "proforma" ? "Proformă" : "Factură fiscală")
                LabeledContent("Serie", value: (item.series?.isEmpty == false) ? (item.series ?? "—") : "implicită")
                LabeledContent("Frecvență", value: item.frequency ?? "—")
                LabeledContent("Următoarea", value: item.nextRunDate ?? "—")
                LabeledContent("Monedă", value: item.currency ?? "RON")
            }
            if let message {
                Section { Text(message) }
            }
            if auth.can("recurring_manage") {
                Section("Acțiuni") {
                    Button("Activează / Pauză") { Task { await toggle() } }
                    Button("Generează acum") { Task { await generate() } }
                }
            }
        }
        .navigationTitle(item.title ?? "Abonament")
    }

    private func toggle() async {
        do {
            let _: DataEnvelope<APIRecurring> = try await APIClient.shared.request(
                "POST", path: "recurring/\(item.id)/toggle"
            )
            message = "Stare actualizată."
            onChanged()
        } catch {
            message = error.localizedDescription
        }
    }

    private func generate() async {
        do {
            struct GenResponse: Decodable {
                let documentId: Int?
                enum CodingKeys: String, CodingKey { case documentId = "document_id" }
            }
            let _: GenResponse = try await APIClient.shared.request(
                "POST", path: "recurring/\(item.id)/generate"
            )
            message = "Factură generată."
            onChanged()
        } catch {
            message = error.localizedDescription
        }
    }
}

struct RecurringEditorView: View {
    @Environment(\.dismiss) private var dismiss
    var onSaved: () -> Void

    @State private var clients: [APIClientDTO] = []
    @State private var clientId: Int?
    @State private var title = ""
    @State private var frequency = "monthly"
    @State private var documentType = "invoice"
    @State private var startDate = DateFormats.today()
    @State private var itemName = "Servicii"
    @State private var quantity = "1"
    @State private var unitPrice = "100"
    @State private var vatRate = "21"
    @State private var error: String?

    var body: some View {
        Form {
            Section {
                Picker("Client", selection: $clientId) {
                    Text("Selectează").tag(Int?.none)
                    ForEach(clients) { c in
                        Text(c.name).tag(Optional(c.id))
                    }
                }
                TextField("Titlu", text: $title)
                Picker("Tip document", selection: $documentType) {
                    Text("Factură fiscală").tag("invoice")
                    Text("Proformă").tag("proforma")
                }
                Picker("Frecvență", selection: $frequency) {
                    Text("Săptămânală").tag("weekly")
                    Text("Lunară").tag("monthly")
                    Text("Trimestrială").tag("quarterly")
                    Text("Semestrială").tag("semiannual")
                    Text("Anuală").tag("annual")
                }
                TextField("Data start", text: $startDate)
            }
            Section("Linie") {
                TextField("Denumire", text: $itemName)
                TextField("Cantitate", text: $quantity).keyboardType(.decimalPad)
                TextField("Preț", text: $unitPrice).keyboardType(.decimalPad)
                Picker("TVA %", selection: $vatRate) {
                    Text("21%").tag("21")
                    Text("11%").tag("11")
                    Text("5%").tag("5")
                    Text("0%").tag("0")
                }
            }
            if let error {
                Section { Text(error).foregroundStyle(.red) }
            }
        }
        .navigationTitle("Recurentă nouă")
        .toolbar {
            ToolbarItem(placement: .cancellationAction) { Button("Închide") { dismiss() } }
            ToolbarItem(placement: .confirmationAction) {
                Button("Salvează") { Task { await save() } }
            }
        }
        .task {
            if let response: DataEnvelope<[APIClientDTO]> = try? await APIClient.shared.request("GET", path: "clients") {
                clients = response.data
            }
        }
    }

    private func save() async {
        guard let clientId else {
            error = "Selectează un client."
            return
        }
        struct ItemBody: Encodable {
            let name: String
            let quantity: Double
            let unitPrice: Double
            let vatRate: Double
            let unit: String
            enum CodingKeys: String, CodingKey {
                case name, quantity, unit
                case unitPrice = "unit_price"
                case vatRate = "vat_rate"
            }
        }
        struct Body: Encodable {
            let clientId: Int
            let title: String
            let frequency: String
            let documentType: String
            let startDate: String
            let currency: String
            let items: [ItemBody]
            enum CodingKeys: String, CodingKey {
                case title, frequency, currency, items
                case clientId = "client_id"
                case documentType = "document_type"
                case startDate = "start_date"
            }
        }
        let body = Body(
            clientId: clientId,
            title: title,
            frequency: frequency,
            documentType: documentType,
            startDate: startDate,
            currency: "RON",
            items: [
                ItemBody(
                    name: itemName,
                    quantity: Double(quantity.replacingOccurrences(of: ",", with: ".")) ?? 1,
                    unitPrice: Double(unitPrice.replacingOccurrences(of: ",", with: ".")) ?? 0,
                    vatRate: Double(vatRate.replacingOccurrences(of: ",", with: ".")) ?? 21,
                    unit: "buc"
                ),
            ]
        )
        do {
            let _: DataEnvelope<APIRecurring> = try await APIClient.shared.request(
                "POST", path: "recurring", body: body
            )
            onSaved()
            dismiss()
        } catch {
            self.error = error.localizedDescription
        }
    }
}
