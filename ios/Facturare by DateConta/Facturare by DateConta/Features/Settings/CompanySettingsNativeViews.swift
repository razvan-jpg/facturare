import SwiftUI
import SwiftData

// MARK: - Serii

struct SeriesSettingsView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(\.modelContext) private var modelContext
    @Query(sort: \LocalSeries.prefix) private var localSeries: [LocalSeries]
    @State private var error: String?
    @State private var showCreate = false

    private var companySeries: [LocalSeries] {
        let companyId = auth.currentCompanyId ?? -1
        return localSeries.filter { $0.companyId == companyId }.sorted {
            if $0.type != $1.type { return $0.type < $1.type }
            if $0.year != $1.year { return $0.year > $1.year }
            return $0.prefix < $1.prefix
        }
    }

    var body: some View {
        List {
            if let error {
                Text(error).foregroundStyle(.red)
            }
            ForEach(companySeries, id: \.serverId) { series in
                NavigationLink {
                    SeriesEditorView(series: series) {
                        Task { await load() }
                    }
                } label: {
                    VStack(alignment: .leading, spacing: 4) {
                        Text("\(series.prefix) · \(series.type) · \(series.year)")
                            .fontWeight(.semibold)
                        Text("De la \(String(format: "%04d", series.firstNumber)) · următorul \(String(format: "%04d", series.nextNumber))\(series.isDefault ? " · implicită" : "")\(series.active ? "" : " · inactivă")")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }
            }
        }
        .navigationTitle("Serii")
        .toolbar {
            if auth.can("settings_manage") {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            NavigationStack {
                SeriesEditorView(series: nil) {
                    Task { await load() }
                }
            }
        }
        .task { await load() }
        .refreshable { await load() }
    }

    private func load() async {
        do {
            struct Resp: Decodable { let data: [APISeries] }
            let response: Resp = try await APIClient.shared.request("GET", path: "series")
            let companyId = auth.currentCompanyId ?? 0
            for s in response.data {
                let sid = s.id
                let year = s.year ?? Calendar.current.component(.year, from: Date())
                let existing = try? modelContext.fetch(
                    FetchDescriptor<LocalSeries>(predicate: #Predicate { $0.serverId == sid })
                ).first
                let local = existing ?? LocalSeries(
                    serverId: sid,
                    companyId: companyId,
                    type: s.type,
                    prefix: s.prefix,
                    firstNumber: s.firstNumber ?? s.nextNumber ?? 1,
                    nextNumber: s.nextNumber ?? 1,
                    year: year,
                    active: s.active ?? true,
                    isDefault: s.isDefault ?? false
                )
                if existing == nil { modelContext.insert(local) }
                local.companyId = companyId
                local.type = s.type
                local.prefix = s.prefix
                local.firstNumber = s.firstNumber ?? s.nextNumber ?? 1
                local.nextNumber = s.nextNumber ?? 1
                local.year = year
                local.active = s.active ?? true
                local.isDefault = s.isDefault ?? false
            }
            try? modelContext.save()
            error = nil
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct SeriesEditorView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(\.dismiss) private var dismiss
    let series: LocalSeries?
    var onSaved: () -> Void

    @State private var type = "invoice"
    @State private var prefix = ""
    @State private var firstNumber = "1"
    @State private var nextNumber = "1"
    @State private var year = String(Calendar.current.component(.year, from: Date()))
    @State private var description = ""
    @State private var active = true
    @State private var isDefault = false
    @State private var error: String?
    @State private var saving = false

    var body: some View {
        Form {
            if series == nil {
                Picker("Tip", selection: $type) {
                    Text("Factură").tag("invoice")
                    Text("Proformă").tag("proforma")
                    Text("Aviz").tag("delivery")
                    Text("Chitanță").tag("receipt")
                    Text("Notă credit").tag("credit_note")
                }
                TextField("Prefix", text: $prefix)
                    .textInputAutocapitalization(.characters)
                TextField("An", text: $year)
                    .keyboardType(.numberPad)
            } else {
                LabeledContent("Tip", value: series?.type ?? "")
                LabeledContent("Prefix", value: series?.prefix ?? "")
                LabeledContent("An", value: "\(series?.year ?? 0)")
            }
            TextField("Primul nr. DateConta", text: $firstNumber)
                .keyboardType(.numberPad)
            TextField("Următorul număr de emis", text: $nextNumber)
                .keyboardType(.numberPad)
            TextField("Descriere", text: $description)
            if series != nil {
                Toggle("Activă", isOn: $active)
            }
            Toggle("Implicită", isOn: $isDefault)
            if let error {
                Text(error).foregroundStyle(.red)
            }
        }
        .navigationTitle(series == nil ? "Serie nouă" : "Editează seria")
        .toolbar {
            ToolbarItem(placement: .cancellationAction) {
                Button("Închide") { dismiss() }
            }
            ToolbarItem(placement: .confirmationAction) {
                Button("Salvează") { Task { await save() } }
                    .disabled(saving)
            }
            if let series, auth.can("settings_manage") {
                ToolbarItem(placement: .bottomBar) {
                    Button("Șterge", role: .destructive) {
                        Task { await deleteSeries(series) }
                    }
                }
            }
        }
        .onAppear {
            if let series {
                type = series.type
                prefix = series.prefix
                firstNumber = "\(series.firstNumber)"
                nextNumber = "\(series.nextNumber)"
                year = "\(series.year)"
                description = ""
                active = series.active
                isDefault = series.isDefault
            }
        }
        .onChange(of: firstNumber) { _, newValue in
            if series == nil, nextNumber == "1" || nextNumber.isEmpty {
                nextNumber = newValue
            }
        }
    }

    private func save() async {
        saving = true
        defer { saving = false }
        guard let first = Int(firstNumber), first >= 1,
              let next = Int(nextNumber), next >= 1 else {
            error = "Numere invalide."
            return
        }
        let nextClamped = max(first, next)
        do {
            if let series {
                struct Body: Encodable {
                    let firstNumber: Int
                    let nextNumber: Int
                    let description: String?
                    let active: Bool
                    let isDefault: Bool
                    enum CodingKeys: String, CodingKey {
                        case description, active
                        case firstNumber = "first_number"
                        case nextNumber = "next_number"
                        case isDefault = "is_default"
                    }
                }
                let _: DataEnvelope<APISeries> = try await APIClient.shared.request(
                    "PUT",
                    path: "series/\(series.serverId)",
                    body: Body(firstNumber: first, nextNumber: nextClamped, description: description.isEmpty ? nil : description, active: active, isDefault: isDefault)
                )
            } else {
                guard let y = Int(year), !prefix.trimmingCharacters(in: .whitespaces).isEmpty else {
                    error = "Completează prefixul și anul."
                    return
                }
                struct Body: Encodable {
                    let type: String
                    let prefix: String
                    let firstNumber: Int
                    let nextNumber: Int
                    let year: Int
                    let description: String?
                    let isDefault: Bool
                    enum CodingKeys: String, CodingKey {
                        case type, prefix, year, description
                        case firstNumber = "first_number"
                        case nextNumber = "next_number"
                        case isDefault = "is_default"
                    }
                }
                let _: DataEnvelope<APISeries> = try await APIClient.shared.request(
                    "POST",
                    path: "series",
                    body: Body(
                        type: type,
                        prefix: prefix.uppercased(),
                        firstNumber: first,
                        nextNumber: nextClamped,
                        year: y,
                        description: description.isEmpty ? nil : description,
                        isDefault: isDefault
                    )
                )
            }
            onSaved()
            dismiss()
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func deleteSeries(_ series: LocalSeries) async {
        do {
            struct Msg: Decodable { let message: String? }
            let _: Msg = try await APIClient.shared.request("DELETE", path: "series/\(series.serverId)")
            onSaved()
            dismiss()
        } catch {
            self.error = error.localizedDescription
        }
    }
}

// MARK: - Cote TVA

struct VatSettingsView: View {
    @Environment(AuthStore.self) private var auth
    @State private var vatPayer = true
    @State private var vatOnCollection = false
    @State private var defaultVatRate = "21"
    @State private var message: String?
    @State private var saving = false

    var body: some View {
        Form {
            Toggle("Plătitor TVA", isOn: $vatPayer)
            Toggle("TVA la încasare", isOn: $vatOnCollection)
            TextField("Cotă TVA implicită (%)", text: $defaultVatRate)
                .keyboardType(.decimalPad)
            if let message {
                Text(message).foregroundStyle(AppTheme.teal)
            }
            if auth.can("settings_manage") {
                Button(saving ? "Se salvează…" : "Salvează") {
                    Task { await save() }
                }
                .disabled(saving)
            }
        }
        .navigationTitle("Cote TVA")
        .task { await load() }
    }

    private func load() async {
        guard let id = auth.currentCompanyId else { return }
        do {
            let response: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "GET", path: "companies/\(id)"
            )
            vatPayer = response.data.vatPayer ?? true
            vatOnCollection = response.data.vatOnCollection ?? false
            if let rate = response.data.defaultVatRate {
                defaultVatRate = String(format: "%g", rate)
            }
        } catch {
            message = error.localizedDescription
        }
    }

    private func save() async {
        guard let id = auth.currentCompanyId, let rate = Double(defaultVatRate.replacingOccurrences(of: ",", with: ".")) else {
            message = "Cotă invalidă."
            return
        }
        saving = true
        defer { saving = false }
        do {
            struct Body: Encodable {
                let vatPayer: Bool
                let vatOnCollection: Bool
                let defaultVatRate: Double
                enum CodingKeys: String, CodingKey {
                    case vatPayer = "vat_payer"
                    case vatOnCollection = "vat_on_collection"
                    case defaultVatRate = "default_vat_rate"
                }
            }
            let _: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "PUT",
                path: "companies/\(id)",
                body: Body(vatPayer: vatPayer, vatOnCollection: vatOnCollection, defaultVatRate: rate)
            )
            message = "Salvat."
        } catch {
            message = error.localizedDescription
        }
    }
}

// MARK: - Limbi documente

struct LanguagesSettingsView: View {
    @Environment(AuthStore.self) private var auth
    private let catalog: [(code: String, label: String)] = [
        ("ro", "Română"), ("en", "Engleză"), ("de", "Germană"),
        ("fr", "Franceză"), ("it", "Italiană"), ("es", "Spaniolă"), ("hu", "Maghiară"),
    ]
    @State private var selected: Set<String> = ["ro"]
    @State private var message: String?
    @State private var saving = false

    var body: some View {
        Form {
            Section {
                ForEach(catalog, id: \.code) { lang in
                    Toggle(lang.label, isOn: Binding(
                        get: { selected.contains(lang.code) },
                        set: { on in
                            if on {
                                selected.insert(lang.code)
                            } else if lang.code != "ro" {
                                selected.remove(lang.code)
                            }
                        }
                    ))
                    .disabled(lang.code == "ro")
                }
            } footer: {
                Text("Româna rămâne întotdeauna activă.")
            }
            if let message {
                Text(message).foregroundStyle(AppTheme.teal)
            }
            if auth.can("settings_manage") {
                Button(saving ? "Se salvează…" : "Salvează") {
                    Task { await save() }
                }
                .disabled(saving)
            }
        }
        .navigationTitle("Limbi")
        .task { await load() }
    }

    private func load() async {
        guard let id = auth.currentCompanyId else { return }
        do {
            let response: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "GET", path: "companies/\(id)"
            )
            if let langs = response.data.documentLanguages, !langs.isEmpty {
                selected = Set(langs)
                selected.insert("ro")
            }
        } catch {
            message = error.localizedDescription
        }
    }

    private func save() async {
        guard let id = auth.currentCompanyId else { return }
        saving = true
        defer { saving = false }
        var langs = Array(selected)
        if !langs.contains("ro") { langs.insert("ro", at: 0) }
        do {
            struct Body: Encodable {
                let documentLanguages: [String]
                enum CodingKeys: String, CodingKey {
                    case documentLanguages = "document_languages"
                }
            }
            let _: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "PUT",
                path: "companies/\(id)",
                body: Body(documentLanguages: langs)
            )
            message = "Salvat."
        } catch {
            message = error.localizedDescription
        }
    }
}

// MARK: - Personalizare PDF (minimal)

struct PdfPersonalizeSettingsView: View {
    @Environment(AuthStore.self) private var auth
    @State private var color = "#0f766e"
    @State private var template = "classic"
    @State private var message: String?
    @State private var saving = false

    private let templates: [(id: String, name: String)] = [
        ("classic", "Clasic"), ("modern", "Modern"), ("compact", "Compact"),
        ("bold", "Bold"), ("elegant", "Elegant"), ("stripe", "Stripe"),
        ("nord", "Nord"), ("ledger", "Ledger"), ("studio", "Studio"),
        ("frame", "Frame"), ("swiss", "Swiss"), ("folio", "Folio"),
        ("split", "Split"), ("ticket", "Ticket"),
    ]

    var body: some View {
        Form {
            TextField("Culoare (hex)", text: $color)
            Picker("Machetă", selection: $template) {
                ForEach(templates, id: \.id) { Text($0.name).tag($0.id) }
            }
            Button("Logo / ștampilă (Safari, autentificat)") {
                Task {
                    if let id = auth.currentCompanyId {
                        await WebSession.open(path: "/companies/\(id)/edit?tab=personalizare")
                    }
                }
            }
            if let message {
                Text(message).foregroundStyle(AppTheme.teal)
            }
            if auth.can("settings_manage") {
                Button(saving ? "Se salvează…" : "Salvează") {
                    Task { await save() }
                }
                .disabled(saving)
            }
        }
        .navigationTitle("Personalizare PDF")
        .task { await load() }
    }

    private func load() async {
        guard let id = auth.currentCompanyId else { return }
        do {
            let response: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "GET", path: "companies/\(id)"
            )
            color = response.data.invoiceColor ?? color
            template = response.data.invoiceTemplate ?? template
        } catch {
            message = error.localizedDescription
        }
    }

    private func save() async {
        guard let id = auth.currentCompanyId else { return }
        saving = true
        defer { saving = false }
        do {
            struct Body: Encodable {
                let invoiceColor: String
                let invoiceTemplate: String
                enum CodingKeys: String, CodingKey {
                    case invoiceColor = "invoice_color"
                    case invoiceTemplate = "invoice_template"
                }
            }
            let _: DataEnvelope<APICompanyDetailed> = try await APIClient.shared.request(
                "PUT",
                path: "companies/\(id)",
                body: Body(invoiceColor: color, invoiceTemplate: template)
            )
            message = "Salvat."
        } catch {
            message = error.localizedDescription
        }
    }
}
