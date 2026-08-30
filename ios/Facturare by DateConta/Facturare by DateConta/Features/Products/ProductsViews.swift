import SwiftUI
import SwiftData

struct ProductsListView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Query(sort: \LocalProduct.name) private var products: [LocalProduct]
    @State private var showCreate = false
    @State private var search = ""

    private var filtered: [LocalProduct] {
        let companyId = auth.currentCompanyId ?? -1
        return products.filter {
            !$0.isDeleted && $0.companyId == companyId &&
            (search.isEmpty || $0.name.localizedCaseInsensitiveContains(search))
        }
    }

    var body: some View {
        List {
            ForEach(filtered, id: \.clientUUID) { product in
                NavigationLink {
                    ProductEditorView(product: product)
                } label: {
                    HStack {
                        VStack(alignment: .leading) {
                            Text(product.name).fontWeight(.semibold)
                            Text("\(product.unit) · TVA \(product.vatRate.formatted())%")
                                .font(.caption).foregroundStyle(.secondary)
                        }
                        Spacer()
                        Text(product.price, format: .currency(code: "RON"))
                        if product.pendingSync {
                            Image(systemName: "arrow.triangle.2.circlepath")
                                .foregroundStyle(AppTheme.warm)
                        }
                    }
                }
            }
            .onDelete(perform: delete)
        }
        .searchable(text: $search)
        .companySyncedList(
            isEmpty: filtered.isEmpty && search.isEmpty,
            emptyTitle: "Niciun produs",
            emptySystemImage: "shippingbox"
        )
        .navigationTitle("Produse")
        .toolbar {
            if auth.can("products_manage") {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            NavigationStack { ProductEditorView(product: nil) }
        }
    }

    private func delete(at offsets: IndexSet) {
        for index in offsets {
            let product = filtered[index]
            product.isDeleted = true
            product.pendingSync = true
            sync.enqueue(entity: "product", action: "delete", clientUUID: product.clientUUID, serverId: product.serverId)
        }
        try? modelContext.save()
    }
}

struct ProductEditorView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Environment(\.dismiss) private var dismiss

    let product: LocalProduct?

    @State private var name = ""
    @State private var unit = "buc"
    @State private var type = "service"
    @State private var price = ""
    @State private var vatRate = "21"
    @State private var description = ""
    @State private var active = true

    var body: some View {
        Form {
            Section {
                TextField("Denumire", text: $name)
                Picker("Tip", selection: $type) {
                    Text("Serviciu").tag("service")
                    Text("Produs").tag("product")
                }
                TextField("UM", text: $unit)
                TextField("Preț", text: $price).keyboardType(.decimalPad)
                TextField("TVA %", text: $vatRate).keyboardType(.decimalPad)
                Toggle("Activ", isOn: $active)
            }
            Section("Descriere") {
                TextField("Opțional", text: $description, axis: .vertical)
            }
        }
        .navigationTitle(product == nil ? "Produs nou" : "Editează produs")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("Salvează") { save() }
                    .disabled(name.isEmpty || !auth.can("products_manage"))
            }
            if product == nil {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Anulează") { dismiss() }
                }
            }
        }
        .onAppear { load() }
    }

    private func load() {
        guard let product else { return }
        name = product.name
        unit = product.unit
        type = product.type
        price = String(product.price)
        vatRate = String(product.vatRate)
        description = product.productDescription ?? ""
        active = product.active
    }

    private func save() {
        guard let companyId = auth.currentCompanyId else { return }
        let target = product ?? LocalProduct(companyId: companyId, name: name)
        if product == nil { modelContext.insert(target) }
        target.name = name
        target.unit = unit
        target.type = type
        target.price = Double(price.replacingOccurrences(of: ",", with: ".")) ?? 0
        target.vatRate = Double(vatRate.replacingOccurrences(of: ",", with: ".")) ?? 21
        target.productDescription = description.isEmpty ? nil : description
        target.active = active
        target.pendingSync = true
        target.updatedAt = .now
        try? modelContext.save()

        sync.enqueue(
            entity: "product",
            action: product == nil ? "create" : "update",
            clientUUID: target.clientUUID,
            serverId: target.serverId,
            payload: [
                "name": target.name,
                "unit": target.unit,
                "type": target.type,
                "price": target.price,
                "vat_rate": target.vatRate,
                "description": target.productDescription as Any,
                "active": target.active,
            ].compactMapValues { $0 is NSNull ? nil : $0 }
        )
        dismiss()
    }
}
