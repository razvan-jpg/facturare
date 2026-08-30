import SwiftUI

struct EfacturaView: View {
    @Environment(AuthStore.self) private var auth
    @State private var status: EfacturaStatusResponse?
    @State private var message: String?

    var body: some View {
        Form {
            Section("SPV ANAF") {
                LabeledContent("Autorizat", value: status?.authorized == true ? "Da" : "Nu")
                if let cif = status?.anafCif {
                    LabeledContent("CIF", value: cif)
                }
                if let mode = status?.sendMode {
                    LabeledContent("Mod trimitere", value: mode)
                }
            }

            if let message {
                Section { Text(message) }
            }

            Section("Acțiuni") {
                if auth.can("efactura_manage") {
                    Button("Autorizează SPV") {
                        Task { await startOAuth() }
                    }
                }
                if let web = status?.webSettingsUrl {
                    Button("Deschide setările pe web") {
                        Task { await WebSession.openURLString(web) }
                    }
                }
                Button("Reîncarcă stare") { Task { await load() } }
            }

            Section {
                Text("Trimiterea facturilor în e-Factura se face din detaliul documentului emis.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
        .navigationTitle("e-Factura")
        .task(id: auth.currentCompanyId) {
            status = nil
            await load()
        }
    }

    private func load() async {
        do {
            status = try await APIClient.shared.request("GET", path: "efactura/status")
            message = nil
        } catch {
            message = error.localizedDescription
        }
    }

    private func startOAuth() async {
        do {
            struct OAuthURL: Decodable { let url: String }
            let response: OAuthURL = try await APIClient.shared.request("GET", path: "efactura/oauth-url")
            guard let url = URL(string: response.url) else {
                message = "URL OAuth invalid."
                return
            }
            // URL ANAF (sau redirect pe domeniul app) — deschide direct, nu prin SSO path.
            await MainActor.run {
                UIApplication.shared.open(url)
            }
            message = "Finalizează autorizarea în Safari, apoi revino în aplicație."
        } catch {
            message = error.localizedDescription
        }
    }
}
