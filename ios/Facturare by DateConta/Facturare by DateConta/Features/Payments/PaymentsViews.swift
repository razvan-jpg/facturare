import SwiftUI
import SwiftData

struct PaymentsListView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Query(sort: \LocalPayment.paidAt, order: .reverse) private var payments: [LocalPayment]
    @Query(sort: \LocalDocument.updatedAt, order: .reverse) private var documents: [LocalDocument]
    @State private var showCreate = false

    private var filtered: [LocalPayment] {
        let companyId = auth.currentCompanyId ?? -1
        return payments.filter { $0.companyId == companyId }
    }

    var body: some View {
        List {
            ForEach(filtered, id: \.clientUUID) { payment in
                HStack {
                    VStack(alignment: .leading) {
                        Text(payment.method.uppercased()).fontWeight(.semibold)
                        Text(payment.paidAt).font(.caption).foregroundStyle(.secondary)
                        if payment.pendingSync {
                            Text("În așteptare sync").font(.caption2).foregroundStyle(AppTheme.warm)
                        }
                    }
                    Spacer()
                    Text(payment.amount, format: .currency(code: payment.currency))
                }
            }
        }
        .companySyncedList(
            isEmpty: filtered.isEmpty,
            emptyTitle: "Nicio încasare",
            emptySystemImage: "banknote"
        )
        .navigationTitle("Încasări")
        .toolbar {
            if auth.can("payments_manage") {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            NavigationStack {
                PaymentCreateView(documents: documents.filter {
                    !$0.isDeleted && $0.companyId == (auth.currentCompanyId ?? -1) && $0.status == "issued" && $0.serverId != nil
                })
            }
        }
    }
}

struct PaymentCreateView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(\.modelContext) private var modelContext
    @Environment(\.dismiss) private var dismiss

    let documents: [LocalDocument]

    @State private var documentId: Int?
    @State private var method = "op"
    @State private var amount = ""
    @State private var paidAt = DateFormats.today()
    @State private var reference = ""
    @State private var error: String?

    var body: some View {
        Form {
            Section {
                Picker("Factură", selection: $documentId) {
                    Text("Selectează").tag(Int?.none)
                    ForEach(documents, id: \.clientUUID) { doc in
                        Text("\(doc.displayTitle) — \(doc.clientName ?? "")")
                            .tag(Optional(doc.serverId))
                    }
                }
                Picker("Metodă", selection: $method) {
                    Text("OP").tag("op")
                    Text("Numerar").tag("cash")
                    Text("Card").tag("card")
                    Text("Altele").tag("other")
                }
                TextField("Sumă", text: $amount).keyboardType(.decimalPad)
                TextField("Data", text: $paidAt)
                TextField("Referință", text: $reference)
            }
            if let error {
                Section { Text(error).foregroundStyle(.red) }
            }
        }
        .navigationTitle("Încasare nouă")
        .toolbar {
            ToolbarItem(placement: .cancellationAction) { Button("Închide") { dismiss() } }
            ToolbarItem(placement: .confirmationAction) { Button("Salvează") { save() } }
        }
    }

    private func save() {
        guard let companyId = auth.currentCompanyId,
              let documentId,
              let value = Double(amount.replacingOccurrences(of: ",", with: ".")),
              value > 0 else {
            error = "Completează factura și suma."
            return
        }

        let payment = LocalPayment(
            companyId: companyId,
            documentServerId: documentId,
            method: method,
            paidAt: paidAt,
            amount: value,
            reference: reference.isEmpty ? nil : reference,
            pendingSync: true
        )
        modelContext.insert(payment)
        try? modelContext.save()

        sync.enqueue(
            entity: "payment",
            action: "create",
            clientUUID: payment.clientUUID,
            payload: [
                "document_id": documentId,
                "method": method,
                "paid_at": paidAt,
                "amount": value,
                "reference": reference,
            ]
        )
        dismiss()
    }
}
