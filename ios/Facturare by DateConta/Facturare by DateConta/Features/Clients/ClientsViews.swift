import SwiftUI
import SwiftData

struct ClientsListView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Query(sort: \LocalClient.name) private var clients: [LocalClient]
    @State private var showCreate = false
    @State private var search = ""

    private var filtered: [LocalClient] {
        let companyId = auth.currentCompanyId ?? -1
        return clients.filter {
            !$0.isDeleted && $0.companyId == companyId &&
            (search.isEmpty || $0.name.localizedCaseInsensitiveContains(search) || ($0.cui ?? "").contains(search))
        }
    }

    var body: some View {
        List {
            ForEach(filtered, id: \.clientUUID) { client in
                NavigationLink {
                    ClientEditorView(client: client)
                } label: {
                    VStack(alignment: .leading, spacing: 4) {
                        HStack {
                            Text(client.name).fontWeight(.semibold)
                            if client.pendingSync {
                                Image(systemName: "arrow.triangle.2.circlepath")
                                    .font(.caption)
                                    .foregroundStyle(AppTheme.warm)
                            }
                        }
                        Text([client.cui, client.city].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " · "))
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }
            }
            .onDelete(perform: delete)
        }
        .searchable(text: $search, prompt: "Caută client")
        .companySyncedList(
            isEmpty: filtered.isEmpty && search.isEmpty,
            emptyTitle: "Niciun client",
            emptySystemImage: "person.2"
        )
        .navigationTitle("Clienți")
        .toolbar {
            if auth.can("clients_manage") {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            NavigationStack { ClientEditorView(client: nil) }
        }
    }

    private func delete(at offsets: IndexSet) {
        for index in offsets {
            let client = filtered[index]
            client.isDeleted = true
            client.pendingSync = true
            client.updatedAt = .now
            sync.enqueue(
                entity: "client",
                action: "delete",
                clientUUID: client.clientUUID,
                serverId: client.serverId
            )
        }
        try? modelContext.save()
    }
}

struct ClientEditorView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Environment(\.dismiss) private var dismiss

    let client: LocalClient?

    @State private var name = ""
    @State private var type = "company"
    @State private var cui = ""
    @State private var regCom = ""
    @State private var cnp = ""
    @State private var address = ""
    @State private var city = ""
    @State private var county = ""
    @State private var email = ""
    @State private var phone = ""
    @State private var notes = ""
    @State private var anafMessage: String?

    var body: some View {
        Form {
            Section("Identificare") {
                Picker("Tip", selection: $type) {
                    Text("Firmă").tag("company")
                    Text("Persoană").tag("person")
                }
                TextField("Denumire", text: $name)
                if type == "company" {
                    TextField("CUI", text: $cui)
                    TextField("Reg. Com.", text: $regCom)
                    Button("Preluare ANAF") { Task { await lookupAnaf() } }
                } else {
                    TextField("CNP", text: $cnp)
                }
                if let anafMessage { Text(anafMessage).font(.caption).foregroundStyle(.secondary) }
            }
            Section("Contact") {
                TextField("Adresă", text: $address)
                TextField("Oraș", text: $city)
                TextField("Județ", text: $county)
                TextField("Email", text: $email).keyboardType(.emailAddress).textInputAutocapitalization(.never)
                TextField("Telefon", text: $phone)
            }
            Section("Note") {
                TextField("Note", text: $notes, axis: .vertical)
            }
        }
        .navigationTitle(client == nil ? "Client nou" : "Editează client")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("Salvează") { save() }
                    .disabled(name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || !auth.can("clients_manage"))
            }
            if client == nil {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Anulează") { dismiss() }
                }
            }
        }
        .onAppear { load() }
    }

    private func load() {
        guard let client else { return }
        name = client.name
        type = client.type
        cui = client.cui ?? ""
        regCom = client.regCom ?? ""
        cnp = client.cnp ?? ""
        address = client.address ?? ""
        city = client.city ?? ""
        county = client.county ?? ""
        email = client.email ?? ""
        phone = client.phone ?? ""
        notes = client.notes ?? ""
    }

    private func save() {
        guard let companyId = auth.currentCompanyId else { return }
        let target = client ?? LocalClient(companyId: companyId, name: name)
        if client == nil { modelContext.insert(target) }
        target.name = name.trimmingCharacters(in: .whitespacesAndNewlines)
        target.type = type
        target.cui = cui.isEmpty ? nil : cui
        target.regCom = regCom.isEmpty ? nil : regCom
        target.cnp = cnp.isEmpty ? nil : cnp
        target.address = address.isEmpty ? nil : address
        target.city = city.isEmpty ? nil : city
        target.county = county.isEmpty ? nil : county
        target.email = email.isEmpty ? nil : email
        target.phone = phone.isEmpty ? nil : phone
        target.notes = notes.isEmpty ? nil : notes
        target.pendingSync = true
        target.updatedAt = .now
        try? modelContext.save()

        let payload: [String: Any] = [
            "name": target.name,
            "type": target.type,
            "cui": target.cui as Any,
            "reg_com": target.regCom as Any,
            "cnp": target.cnp as Any,
            "address": target.address as Any,
            "city": target.city as Any,
            "county": target.county as Any,
            "email": target.email as Any,
            "phone": target.phone as Any,
            "notes": target.notes as Any,
        ]
        sync.enqueue(
            entity: "client",
            action: client == nil ? "create" : "update",
            clientUUID: target.clientUUID,
            serverId: target.serverId,
            payload: payload.compactMapValues { $0 is NSNull ? nil : $0 }
        )
        dismiss()
    }

    private func lookupAnaf() async {
        do {
            let data = try await APIClient.shared.rawRequest(
                "POST",
                path: "clients/anaf-lookup",
                body: ["cui": cui]
            )
            if let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
               let payload = json["data"] as? [String: Any] {
                name = (payload["name"] as? String) ?? name
                address = (payload["address"] as? String) ?? address
                city = (payload["city"] as? String) ?? city
                county = (payload["county"] as? String) ?? county
                regCom = (payload["reg_com"] as? String) ?? regCom
                anafMessage = "Date preluate din ANAF."
            }
        } catch {
            anafMessage = error.localizedDescription
        }
    }
}
