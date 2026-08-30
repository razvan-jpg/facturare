import SwiftUI
import SwiftData

struct DocumentsListView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Query(sort: \LocalDocument.updatedAt, order: .reverse) private var documents: [LocalDocument]
    @State private var filterType: String
    @State private var showCreate = false

    init(initialFilter: String = "all") {
        _filterType = State(initialValue: initialFilter)
    }

    private var filtered: [LocalDocument] {
        let companyId = auth.currentCompanyId ?? -1
        return documents.filter { doc in
            guard !doc.isDeleted, doc.companyId == companyId else { return false }
            if filterType == "all" { return true }
            if filterType == "storno" { return doc.status == "storno" || doc.type == "storno" }
            return doc.type == filterType
        }
    }

    private var title: String {
        switch filterType {
        case "invoice": return "Facturi"
        case "proforma": return "Proforme"
        case "delivery": return "Avize"
        case "receipt": return "Chitanțe"
        case "storno": return "Facturi storno"
        case "credit_note": return "Note de creditare"
        default: return "Documente"
        }
    }

    var body: some View {
        VStack(spacing: 0) {
            if filterType == "all" {
                Picker("Tip", selection: $filterType) {
                    Text("Toate").tag("all")
                    Text("Facturi").tag("invoice")
                    Text("Proforme").tag("proforma")
                    Text("Avize").tag("delivery")
                    Text("Chitanțe").tag("receipt")
                    Text("Storno").tag("storno")
                    Text("N. credit").tag("credit_note")
                }
                .pickerStyle(.segmented)
                .padding()
            }

            List {
                ForEach(filtered, id: \.clientUUID) { doc in
                    NavigationLink {
                        DocumentDetailView(document: doc)
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            HStack {
                                Text(doc.displayTitle).fontWeight(.semibold)
                                Spacer()
                                Text(doc.total, format: .currency(code: doc.currency))
                            }
                            Text("\(doc.clientName ?? "Fără client") · \(statusLabel(doc))")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                            if doc.pendingSync || doc.pendingIssue {
                                Label("În așteptare sync", systemImage: "arrow.triangle.2.circlepath")
                                    .font(.caption2)
                                    .foregroundStyle(AppTheme.warm)
                            }
                        }
                    }
                }
            }
            .companySyncedList(
                isEmpty: filtered.isEmpty,
                emptyTitle: "Niciun document",
                emptySystemImage: "doc.text"
            )
        }
        .navigationTitle(title)
        .toolbar {
            if auth.can("documents_manage"), ["all", "invoice", "proforma", "delivery"].contains(filterType) {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            NavigationStack {
                DocumentEditorView(
                    document: nil,
                    initialType: filterType == "all" ? "invoice" : filterType
                )
            }
        }
    }

    private func statusLabel(_ doc: LocalDocument) -> String {
        switch doc.status {
        case "draft": return "Ciornă"
        case "issued": return "Emisă"
        case "cancelled": return "Anulată"
        case "storno": return "Storno"
        default: return doc.status
        }
    }
}

struct DocumentDetailView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Bindable var document: LocalDocument
    @State private var message: String?
    @State private var pdfURL: URL?
    @State private var showEditor = false
    @State private var showShare = false

    var body: some View {
        List {
            Section("Document") {
                LabeledContent("Număr", value: document.displayTitle)
                LabeledContent("Status", value: document.status)
                LabeledContent("Client", value: document.clientName ?? "—")
                LabeledContent("Data", value: document.issueDate)
                LabeledContent("Total", value: document.total.formatted(.currency(code: document.currency)))
                if let ef = document.efacturaStatus, ef != "none" {
                    LabeledContent("e-Factura", value: ef)
                }
                if let err = document.efacturaError, !err.isEmpty {
                    Text(err).foregroundStyle(.red).font(.caption)
                }
            }

            Section("Linii") {
                ForEach(document.items) { item in
                    HStack {
                        VStack(alignment: .leading) {
                            Text(item.name)
                            Text("\(item.quantity) \(item.unit) × \(item.unitPrice)")
                                .font(.caption).foregroundStyle(.secondary)
                        }
                        Spacer()
                        Text(item.lineTotal, format: .currency(code: document.currency))
                    }
                }
            }

            if let message {
                Section { Text(message).foregroundStyle(AppTheme.teal) }
            }

            Section("Acțiuni") {
                if document.status == "draft" && auth.can("documents_manage") {
                    Button("Editează") { showEditor = true }
                    Button("Emite document") { Task { await issue() } }
                }
                if document.serverId != nil {
                    Button("Deschide PDF") { Task { await openPDF() } }
                }
                if ["invoice", "credit_note"].contains(document.type),
                   ["issued", "storno"].contains(document.status),
                   auth.can("efactura_manage"),
                   let sid = document.serverId {
                    Button("Trimite e-Factura") { Task { await sendEfactura(id: sid) } }
                    Button("Actualizează stare e-Factura") { Task { await refreshEfactura(id: sid) } }
                }
                if document.status == "issued", document.type == "invoice", auth.can("documents_manage"), let sid = document.serverId {
                    Button("Storno") { Task { await storno(id: sid) } }
                    Button("Notă de creditare") { Task { await creditNote(id: sid) } }
                }
            }
        }
        .navigationTitle(document.displayTitle)
        .sheet(isPresented: $showEditor) {
            NavigationStack { DocumentEditorView(document: document) }
        }
        .sheet(isPresented: $showShare) {
            if let pdfURL {
                NavigationStack {
                    ShareLink(item: pdfURL) {
                        Label("Partajează PDF", systemImage: "square.and.arrow.up")
                    }
                    .padding()
                    .navigationTitle("PDF")
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) {
                            Button("Închide") { showShare = false }
                        }
                    }
                }
            }
        }
    }

    private func issue() async {
        if let sid = document.serverId {
            do {
                let response: DataEnvelope<APIDocument> = try await APIClient.shared.request(
                    "POST", path: "documents/\(sid)/issue"
                )
                applyServer(response.data)
                message = "Document emis."
                await sync.syncNow()
            } catch {
                // queue for when online
                document.pendingIssue = true
                document.pendingSync = true
                try? modelContext.save()
                sync.enqueue(entity: "document", action: "issue", clientUUID: document.clientUUID, serverId: sid)
                message = "Emiterea a fost pusă în coadă: \(error.localizedDescription)"
            }
        } else {
            document.pendingIssue = true
            document.pendingSync = true
            try? modelContext.save()
            sync.enqueue(entity: "document", action: "issue", clientUUID: document.clientUUID, serverId: nil)
            message = "Documentul va fi emis după sincronizare."
        }
    }

    private func openPDF() async {
        guard let sid = document.serverId else { return }
        do {
            let data = try await APIClient.shared.downloadPDF(documentId: sid)
            let url = FileManager.default.temporaryDirectory.appendingPathComponent("\(document.displayTitle).pdf")
            try data.write(to: url)
            pdfURL = url
            showShare = true
            message = "PDF pregătit."
        } catch {
            message = error.localizedDescription
        }
    }

    private func sendEfactura(id: Int) async {
        do {
            let response: DataEnvelope<APIDocument> = try await APIClient.shared.request(
                "POST", path: "documents/\(id)/efactura/send"
            )
            applyServer(response.data)
            message = "Trimis în e-Factura."
        } catch {
            message = error.localizedDescription
        }
    }

    private func refreshEfactura(id: Int) async {
        do {
            let response: DataEnvelope<APIDocument> = try await APIClient.shared.request(
                "POST", path: "documents/\(id)/efactura/refresh"
            )
            applyServer(response.data)
            message = "Stare e-Factura actualizată."
        } catch {
            message = error.localizedDescription
        }
    }

    private func storno(id: Int) async {
        do {
            let _: DataEnvelope<APIDocument> = try await APIClient.shared.request(
                "POST", path: "documents/\(id)/storno"
            )
            message = "Storno creat."
            await sync.syncNow()
        } catch {
            message = error.localizedDescription
        }
    }

    private func creditNote(id: Int) async {
        do {
            let _: DataEnvelope<APIDocument> = try await APIClient.shared.request(
                "POST", path: "documents/\(id)/credit-note"
            )
            message = "Notă de creditare creată."
            await sync.syncNow()
        } catch {
            message = error.localizedDescription
        }
    }

    private func applyServer(_ d: APIDocument) {
        document.serverId = d.id
        document.status = d.status
        document.numberFull = d.numberFull
        document.number = d.number
        document.series = d.series
        document.total = d.total ?? document.total
        document.subtotal = d.subtotal ?? document.subtotal
        document.vatTotal = d.vatTotal ?? document.vatTotal
        document.efacturaStatus = d.efacturaStatus
        document.efacturaError = d.efacturaError
        document.pendingIssue = false
        document.pendingSync = false
        try? modelContext.save()
    }
}

struct DocumentEditorView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Environment(\.dismiss) private var dismiss
    @Query(sort: \LocalClient.name) private var clients: [LocalClient]
    @Query(sort: \LocalProduct.name) private var products: [LocalProduct]
    @Query(sort: \LocalSeries.prefix) private var localSeries: [LocalSeries]

    let document: LocalDocument?

    @State private var type: String

    init(document: LocalDocument?, initialType: String = "invoice") {
        self.document = document
        _type = State(initialValue: document?.type ?? initialType)
    }
    @State private var clientUUID: String?
    @State private var issueDate = DateFormats.today()
    @State private var dueDate = ""
    @State private var notes = ""
    @State private var selectedSeriesPrefix: String = ""
    @State private var remoteSeries: [APISeries] = []
    @State private var items: [LineItemDraft] = [
        LineItemDraft(name: "", unit: "buc", quantity: 1, unitPrice: 0, vatRate: 21)
    ]
    @State private var issueAfterSave = false
    @State private var reservedNumberFull: String?
    @State private var workingDocument: LocalDocument?
    @State private var didKeepDocument = false
    @State private var didAbandon = false
    @State private var reservationHint: String = "se rezervă…"
    @State private var availableNumbers: [Int] = []
    @State private var gapNumbers: [Int] = []
    @State private var selectedNumber: Int?

    private var activeDocument: LocalDocument? { workingDocument ?? document }

    private var isIssued: Bool {
        guard let activeDocument else { return false }
        return activeDocument.status != "draft" && !(activeDocument.numberFull ?? "").isEmpty
    }

    private var companyClients: [LocalClient] {
        let companyId = auth.currentCompanyId ?? -1
        return clients.filter { !$0.isDeleted && $0.companyId == companyId && $0.serverId != nil }
    }

    private var companyProducts: [LocalProduct] {
        let companyId = auth.currentCompanyId ?? -1
        return products.filter { !$0.isDeleted && $0.active && $0.companyId == companyId }
    }

    private var issueYear: Int {
        Int(issueDate.prefix(4)) ?? Calendar.current.component(.year, from: Date())
    }

    /// Serii active pentru tip + anul emiterii (API dacă e disponibil, altfel cache sync).
    private var availableSeries: [(prefix: String, next: Int, isDefault: Bool)] {
        let companyId = auth.currentCompanyId ?? -1
        if !remoteSeries.isEmpty {
            return remoteSeries
                .filter { $0.type == type && ($0.year ?? 0) == issueYear && ($0.active ?? true) }
                .map { ($0.prefix, $0.nextNumber ?? 1, $0.isDefault ?? false) }
        }
        return localSeries
            .filter { $0.companyId == companyId && $0.type == type && $0.year == issueYear && $0.active }
            .map { ($0.prefix, $0.nextNumber, $0.isDefault) }
    }

    private var selectedSeriesNext: Int {
        availableSeries.first(where: { $0.prefix == selectedSeriesPrefix })?.next ?? 1
    }

    private var previewNumberFull: String {
        if isIssued, let full = activeDocument?.numberFull, !full.isEmpty { return full }
        if let reservedNumberFull, !reservedNumberFull.isEmpty { return reservedNumberFull }
        guard !selectedSeriesPrefix.isEmpty else { return "—" }
        return "\(selectedSeriesPrefix)-\(String(format: "%04d", selectedSeriesNext))"
    }

    private var previewLabel: String {
        if isIssued { return "număr emis" }
        if reservedNumberFull != nil { return "număr rezervat" }
        return reservationHint
    }

    var body: some View {
        Form {
            Section("Document") {
                Picker("Tip", selection: $type) {
                    Text("Factură").tag("invoice")
                    Text("Proformă").tag("proforma")
                    Text("Aviz").tag("delivery")
                    Text("Chitanță").tag("receipt")
                }
                .onChange(of: type) { _, _ in pickDefaultSeries() }

                Picker("Client", selection: $clientUUID) {
                    Text("Selectează").tag(String?.none)
                    ForEach(companyClients, id: \.clientUUID) { c in
                        Text(c.name).tag(Optional(c.clientUUID))
                    }
                }
                TextField("Data emitere (yyyy-mm-dd)", text: $issueDate)
                    .onChange(of: issueDate) { _, _ in pickDefaultSeries() }
                TextField("Scadență", text: $dueDate)
                TextField("Note", text: $notes, axis: .vertical)
            }

            Section {
                if isIssued {
                    LabeledContent("Serie și număr", value: activeDocument?.numberFull ?? "—")
                    Text("număr emis")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                } else if availableSeries.isEmpty {
                    Text("Nu există serii active pentru \(type) în anul \(issueYear). Configurează în Setări → Serii.")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                } else {
                    Picker("Serie", selection: $selectedSeriesPrefix) {
                        ForEach(availableSeries, id: \.prefix) { s in
                            Text("\(s.prefix) — următorul nr. \(String(format: "%04d", s.next))")
                                .tag(s.prefix)
                        }
                    }
                    .onChange(of: selectedSeriesPrefix) { _, _ in
                        Task { await reserveNumber() }
                    }
                    VStack(spacing: 4) {
                        Text(previewNumberFull)
                            .font(.title2.weight(.bold))
                            .foregroundStyle(AppTheme.deep)
                            .frame(maxWidth: .infinity, alignment: .center)
                        Text(previewLabel)
                            .font(.caption.weight(.semibold))
                            .foregroundStyle(.secondary)
                            .frame(maxWidth: .infinity, alignment: .center)
                    }
                    .padding(.vertical, 6)
                    if !availableNumbers.isEmpty {
                        Picker("Număr liber", selection: Binding(
                            get: { selectedNumber ?? availableNumbers.first ?? 0 },
                            set: { newVal in
                                selectedNumber = newVal
                                Task { await reserveNumber(preferred: newVal) }
                            }
                        )) {
                            ForEach(availableNumbers, id: \.self) { n in
                                let label: String = {
                                    var s = "\(selectedSeriesPrefix)-\(String(format: "%04d", n))"
                                    if gapNumbers.contains(n) { s += " · gol" }
                                    else if selectedNumber == n { s += " · rezervat" }
                                    else { s += " · următor" }
                                    return s
                                }()
                                Text(label).tag(n)
                            }
                        }
                    }
                    if !gapNumbers.isEmpty {
                        Text("Goluri libere: " + gapNumbers.prefix(12).map { String(format: "%04d", $0) }.joined(separator: ", ") + (gapNumbers.count > 12 ? "…" : ""))
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                    }
                }
            } header: {
                Text("Serie și număr")
            } footer: {
                if !isIssued {
                    Text("Se rezervă automat cel mai mic număr liber (inclusiv goluri). Poți alege alt număr liber din listă. Offline: se alocă la sincronizare.")
                }
            }

            Section("Linii") {
                ForEach($items) { $item in
                    VStack(alignment: .leading, spacing: 8) {
                        TextField("Denumire", text: $item.name)
                        HStack {
                            TextField("Cant.", value: $item.quantity, format: .number)
                            TextField("Preț", value: $item.unitPrice, format: .number)
                            TextField("TVA", value: $item.vatRate, format: .number)
                        }
                        .keyboardType(.decimalPad)
                        if !companyProducts.isEmpty {
                            Menu("Din catalog") {
                                ForEach(companyProducts, id: \.clientUUID) { p in
                                    Button(p.name) {
                                        item.name = p.name
                                        item.unit = p.unit
                                        item.unitPrice = p.price
                                        item.vatRate = p.vatRate
                                        item.productId = p.serverId
                                    }
                                }
                            }
                        }
                    }
                }
                .onDelete { items.remove(atOffsets: $0) }

                Button("Adaugă linie") {
                    items.append(LineItemDraft(name: "", unit: "buc", quantity: 1, unitPrice: 0, vatRate: 21))
                }
            }

            if activeDocument == nil || activeDocument?.status == "draft" {
                Section {
                    Toggle("Emite după salvare (necesită internet)", isOn: $issueAfterSave)
                }
            }
        }
        .navigationTitle(document == nil ? "Document nou" : "Editează")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("Salvează") { save() }
            }
            ToolbarItem(placement: .cancellationAction) {
                Button("Închide") {
                    Task {
                        await abandonIfNeeded()
                        dismiss()
                    }
                }
            }
        }
        .task {
            workingDocument = document
            load()
            await refreshSeries()
            pickDefaultSeries()
            await ensureReservation()
        }
        .onChange(of: issueDate) { _, _ in
            pickDefaultSeries()
            Task { await reserveNumber() }
        }
        .onDisappear {
            Task { await abandonIfNeeded() }
        }
    }

    private func load() {
        guard let document = activeDocument else { return }
        type = document.type
        issueDate = document.issueDate
        dueDate = document.dueDate ?? ""
        notes = document.notes ?? ""
        items = document.items.isEmpty ? items : document.items
        if let series = document.series, !series.isEmpty {
            selectedSeriesPrefix = series
        }
        if let full = document.numberFull, document.status == "draft", !full.isEmpty {
            reservedNumberFull = full
        }
        if let cid = document.clientServerId {
            clientUUID = companyClients.first(where: { $0.serverId == cid })?.clientUUID
        }
    }

    private func ensureReservation() async {
        guard !isIssued else { return }
        if activeDocument?.serverId == nil {
            await createShellDraftAndReserve()
        } else {
            await reserveNumber()
        }
    }

    private func createShellDraftAndReserve() async {
        guard let companyId = auth.currentCompanyId else { return }
        reservationHint = "se rezervă…"
        do {
            struct Body: Encodable {
                let type: String
                let issueDate: String
                let currency: String
                let series: String?
                let items: [Item]
                let action: String
                enum CodingKeys: String, CodingKey {
                    case type, currency, series, items, action
                    case issueDate = "issue_date"
                }
                struct Item: Encodable {
                    let name: String
                    let unit: String
                    let quantity: Double
                    let unitPrice: Double
                    let vatRate: Double
                    enum CodingKeys: String, CodingKey {
                        case name, unit, quantity
                        case unitPrice = "unit_price"
                        case vatRate = "vat_rate"
                    }
                }
            }
            let body = Body(
                type: type,
                issueDate: issueDate,
                currency: "RON",
                series: selectedSeriesPrefix.isEmpty ? nil : selectedSeriesPrefix,
                items: [.init(name: "—", unit: "buc", quantity: 1, unitPrice: 0, vatRate: 21)],
                action: "draft"
            )
            let response: DataEnvelope<APIDocument> = try await APIClient.shared.request(
                "POST", path: "documents", body: body
            )
            let local = LocalDocument(companyId: companyId, issueDate: issueDate)
            local.serverId = response.data.id
            local.type = response.data.type
            local.status = response.data.status
            local.series = response.data.series
            local.numberFull = response.data.numberFull
            local.number = response.data.number
            modelContext.insert(local)
            try? modelContext.save()
            workingDocument = local
            reservedNumberFull = response.data.numberFull
            selectedNumber = response.data.number
            if let series = response.data.series { selectedSeriesPrefix = series }
            reservationHint = "număr rezervat"
            startTouchLoop(serverId: response.data.id)
            await reserveNumber() // reîncarcă lista de goluri
        } catch {
            reservationHint = "se alocă la sincronizare"
        }
    }

    private func reserveNumber(preferred: Int? = nil) async {
        guard let sid = activeDocument?.serverId, !isIssued else {
            if activeDocument?.serverId == nil { reservationHint = "se alocă la sincronizare" }
            return
        }
        reservationHint = "se rezervă…"
        do {
            struct Body: Encodable {
                let series: String?
                let issueDate: String
                let number: Int?
                enum CodingKeys: String, CodingKey {
                    case series, number
                    case issueDate = "issue_date"
                }
            }
            struct ReserveResp: Decodable {
                let data: APIDocument
                let availableNumbers: [Int]?
                let gapNumbers: [Int]?
                let nextNumber: Int?
                enum CodingKeys: String, CodingKey {
                    case data
                    case availableNumbers = "available_numbers"
                    case gapNumbers = "gap_numbers"
                    case nextNumber = "next_number"
                }
            }
            let response: ReserveResp = try await APIClient.shared.request(
                "POST",
                path: "documents/\(sid)/reserve-number",
                body: Body(
                    series: selectedSeriesPrefix.isEmpty ? nil : selectedSeriesPrefix,
                    issueDate: issueDate,
                    number: preferred
                )
            )
            reservedNumberFull = response.data.numberFull
            selectedNumber = response.data.number
            availableNumbers = response.availableNumbers ?? []
            gapNumbers = response.gapNumbers ?? []
            if let n = response.data.number, !availableNumbers.contains(n) {
                availableNumbers.append(n)
                availableNumbers.sort()
            }
            activeDocument?.numberFull = response.data.numberFull
            activeDocument?.series = response.data.series
            activeDocument?.number = response.data.number
            if let series = response.data.series { selectedSeriesPrefix = series }
            try? modelContext.save()
            reservationHint = "număr rezervat"
            startTouchLoop(serverId: sid)
        } catch {
            reservationHint = "se alocă la sincronizare"
        }
    }

    private func startTouchLoop(serverId: Int) {
        Task {
            while !didKeepDocument && !Task.isCancelled {
                try? await Task.sleep(for: .seconds(120))
                if didKeepDocument { break }
                struct Empty: Encodable {}
                _ = try? await APIClient.shared.request(
                    "POST",
                    path: "documents/\(serverId)/touch-number",
                    body: Empty()
                ) as DataEnvelope<APIDocument>
            }
        }
    }

    private func abandonIfNeeded() async {
        guard !didAbandon, !didKeepDocument, !isIssued, let sid = activeDocument?.serverId else { return }
        let hasRealLines = items.contains {
            let n = $0.name.trimmingCharacters(in: .whitespacesAndNewlines)
            return !n.isEmpty && n != "—"
        }
        guard !hasRealLines else { return }
        didAbandon = true
        _ = try? await APIClient.shared.rawRequest("DELETE", path: "documents/\(sid)")
        if let local = workingDocument, document == nil {
            modelContext.delete(local)
            try? modelContext.save()
        }
    }

    private func pickDefaultSeries() {
        let options = availableSeries
        guard !options.isEmpty else {
            selectedSeriesPrefix = ""
            return
        }
        if options.contains(where: { $0.prefix == selectedSeriesPrefix }) { return }
        if let preferred = document?.series, options.contains(where: { $0.prefix == preferred }) {
            selectedSeriesPrefix = preferred
            return
        }
        selectedSeriesPrefix = options.first(where: \.isDefault)?.prefix ?? options[0].prefix
    }

    private func refreshSeries() async {
        do {
            struct SeriesResponse: Decodable { let data: [APISeries] }
            let response: SeriesResponse = try await APIClient.shared.request("GET", path: "series")
            remoteSeries = response.data
            let companyId = auth.currentCompanyId ?? 0
            for s in response.data {
                let sid = s.id
                let year = s.year ?? issueYear
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
                local.updatedAt = .now
            }
            try? modelContext.save()
        } catch {
            // Folosește seriile din sync local.
        }
    }

    private func save() {
        guard let companyId = auth.currentCompanyId else { return }
        let validItems = items.filter {
            let n = $0.name.trimmingCharacters(in: .whitespacesAndNewlines)
            return !n.isEmpty && n != "—"
        }
        guard !validItems.isEmpty else { return }

        didKeepDocument = true

        let selectedClient = companyClients.first(where: { $0.clientUUID == clientUUID })
        let subtotal = validItems.map(\.lineSubtotal).reduce(0, +)
        let vat = validItems.map(\.lineVat).reduce(0, +)
        let total = validItems.map(\.lineTotal).reduce(0, +)

        let target = activeDocument ?? LocalDocument(companyId: companyId, issueDate: issueDate)
        if activeDocument == nil { modelContext.insert(target) }
        workingDocument = target
        target.type = type
        target.issueDate = issueDate
        target.dueDate = dueDate.isEmpty ? nil : dueDate
        target.notes = notes.isEmpty ? nil : notes
        target.series = selectedSeriesPrefix.isEmpty ? nil : selectedSeriesPrefix
        target.numberFull = reservedNumberFull ?? target.numberFull
        target.clientServerId = selectedClient?.serverId
        target.clientName = selectedClient?.name
        target.clientCui = selectedClient?.cui
        target.clientEmail = selectedClient?.email
        target.items = validItems
        target.subtotal = subtotal
        target.vatTotal = vat
        target.total = total
        target.pendingSync = true
        target.pendingIssue = issueAfterSave
        target.updatedAt = .now
        if target.status != "draft" { target.status = "draft" }
        try? modelContext.save()

        var payload: [String: Any] = [
            "type": type,
            "client_id": selectedClient?.serverId as Any,
            "issue_date": issueDate,
            "due_date": dueDate.isEmpty ? NSNull() : dueDate,
            "currency": "RON",
            "notes": notes,
            "items": validItems.map { item -> [String: Any] in
                [
                    "name": item.name,
                    "unit": item.unit,
                    "quantity": item.quantity,
                    "unit_price": item.unitPrice,
                    "vat_rate": item.vatRate,
                    "product_id": item.productId as Any,
                ]
            },
            "action": issueAfterSave ? "issue" : "draft",
        ]
        if !selectedSeriesPrefix.isEmpty {
            payload["series"] = selectedSeriesPrefix
        }

        sync.enqueue(
            entity: "document",
            action: target.serverId == nil ? "create" : "update",
            clientUUID: target.clientUUID,
            serverId: target.serverId,
            payload: payload
        )

        if issueAfterSave, let sid = target.serverId {
            sync.enqueue(entity: "document", action: "issue", clientUUID: target.clientUUID, serverId: sid)
        }

        dismiss()
    }
}
