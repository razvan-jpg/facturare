import SwiftUI

struct ReportsView: View {
    @Environment(AuthStore.self) private var auth
    var focusBalance: Bool = false

    @State private var summary: ReportSummary?
    @State private var from: String = {
        let f = DateFormatter()
        f.locale = Locale(identifier: "en_US_POSIX")
        f.dateFormat = "yyyy-MM-01"
        return f.string(from: Date())
    }()
    @State private var to = DateFormats.today()
    @State private var error: String?
    @State private var balanceJSON: String?

    var body: some View {
        Form {
            Section("Perioadă") {
                TextField("De la (yyyy-mm-dd)", text: $from)
                TextField("Până la", text: $to)
                Button("Actualizează") {
                    Task {
                        await load()
                        if focusBalance { await loadBalance() }
                    }
                }
            }

            if !focusBalance, let summary {
                Section("Sumar") {
                    LabeledContent("Vânzări", value: money(summary.salesTotal))
                    LabeledContent("Încasări", value: money(summary.paymentsTotal))
                    LabeledContent("Neîncasat", value: money(summary.unpaidTotal))
                    LabeledContent("Facturi", value: "\(summary.documentsCount)")
                }
            }

            if let error {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section(focusBalance ? "Clienți (solduri)" : "Balanță parteneri") {
                Button("Încarcă balanța") { Task { await loadBalance() } }
                if let balanceJSON {
                    Text(balanceJSON)
                        .font(.caption.monospaced())
                        .foregroundStyle(.secondary)
                }
            }
        }
        .navigationTitle(focusBalance ? "Clienți (solduri)" : "Vânzări și încasări")
        .task(id: auth.currentCompanyId) {
            summary = nil
            balanceJSON = nil
            if focusBalance {
                await loadBalance()
            } else {
                await load()
            }
        }
    }

    private func load() async {
        do {
            summary = try await APIClient.shared.request(
                "GET",
                path: "reports/summary",
                query: [
                    URLQueryItem(name: "from", value: from),
                    URLQueryItem(name: "to", value: to),
                ]
            )
            error = nil
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func loadBalance() async {
        do {
            let data = try await APIClient.shared.rawRequest(
                "GET",
                path: "reports/partners-balance",
                query: [
                    URLQueryItem(name: "from", value: from),
                    URLQueryItem(name: "to", value: to),
                ]
            )
            if let obj = try JSONSerialization.jsonObject(with: data) as? [String: Any],
               let pretty = try? JSONSerialization.data(withJSONObject: obj, options: [.prettyPrinted]),
               let text = String(data: pretty, encoding: .utf8) {
                balanceJSON = String(text.prefix(4000))
            }
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func money(_ value: Double) -> String {
        value.formatted(.currency(code: "RON"))
    }
}
