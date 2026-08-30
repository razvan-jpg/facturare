import SwiftUI
import StoreKit

struct PaywallView: View {
    @Environment(SubscriptionStore.self) private var subscription
    @Environment(AuthStore.self) private var auth

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    Image("BrandLogo")
                        .resizable()
                        .scaledToFill()
                        .frame(width: 64, height: 64)
                        .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))

                    Text("Abonament Facturare")
                        .font(.largeTitle.bold())
                        .foregroundStyle(AppTheme.deep)

                    Text("Perioada gratuită / de test pe iOS s-a încheiat. Pentru a continua pe iPhone/iPad alege un abonament App Store (1, 3, 6 luni sau 1 an).")
                        .font(.body)
                        .foregroundStyle(.secondary)

                    VStack(alignment: .leading, spacing: 8) {
                        Label("Reînnoire automată, poți anula oricând din Setări Apple", systemImage: "arrow.triangle.2.circlepath")
                        Label("Separat de abonamentul web de pe factura.dateconta.ro", systemImage: "info.circle")
                    }
                    .font(.subheadline)
                    .foregroundStyle(AppTheme.deep)

                    if subscription.sortedProducts.isEmpty {
                        Text("Se încarcă planurile…")
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    } else {
                        VStack(spacing: 10) {
                            ForEach(subscription.sortedProducts, id: \.id) { product in
                                planRow(product)
                            }
                        }
                    }

                    if let error = subscription.errorMessage {
                        Text(error)
                            .font(.footnote)
                            .foregroundStyle(.red)
                    }

                    Button {
                        Task { await subscription.purchase() }
                    } label: {
                        Group {
                            if subscription.purchaseInFlight {
                                ProgressView()
                                    .tint(.white)
                            } else {
                                Text(subscribeButtonTitle)
                                    .fontWeight(.semibold)
                            }
                        }
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 14)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(AppTheme.teal)
                    .disabled(subscription.purchaseInFlight || subscription.selectedProduct == nil)

                    Button("Restaurează cumpărăturile") {
                        Task { await subscription.restore() }
                    }
                    .frame(maxWidth: .infinity)

                    VStack(alignment: .leading, spacing: 6) {
                        Link("Termeni și condiții", destination: APIConfig.webBaseURL.appending(path: "legal/termeni"))
                        Link("Confidențialitate", destination: APIConfig.webBaseURL.appending(path: "legal/confidentialitate"))
                        Link("Anulare / rambursare", destination: APIConfig.webBaseURL.appending(path: "legal/anulare"))
                    }
                    .font(.footnote)

                    Text("Plata va fi debitată din contul Apple ID. Abonamentul se reînnoiește automat dacă nu este anulat cu cel puțin 24 de ore înainte de sfârșitul perioadei curente.")
                        .font(.caption2)
                        .foregroundStyle(.secondary)

                    Button("Ieși din cont", role: .destructive) {
                        Task { await auth.logout() }
                    }
                    .padding(.top, 8)
                }
                .padding(24)
            }
            .background(Color(.systemGroupedBackground))
            .navigationBarTitleDisplayMode(.inline)
            .task {
                await subscription.start()
            }
        }
    }

    private var subscribeButtonTitle: String {
        guard let product = subscription.selectedProduct else { return "Abonează-te" }
        let period = SubscriptionConfig.periodLabel(for: product.id)
        return "Abonează-te — \(product.displayPrice) / \(period)"
    }

    private func planRow(_ product: Product) -> some View {
        let selected = subscription.selectedProductId == product.id
        return Button {
            subscription.selectProduct(product.id)
        } label: {
            HStack(alignment: .center, spacing: 12) {
                Image(systemName: selected ? "checkmark.circle.fill" : "circle")
                    .foregroundStyle(selected ? AppTheme.teal : .secondary)
                VStack(alignment: .leading, spacing: 2) {
                    Text(SubscriptionConfig.periodLabel(for: product.id))
                        .font(.headline)
                        .foregroundStyle(AppTheme.deep)
                    Text(product.displayName)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Text(product.displayPrice)
                    .font(.title3.weight(.semibold))
                    .foregroundStyle(AppTheme.teal)
            }
            .padding(14)
            .background(
                RoundedRectangle(cornerRadius: 12, style: .continuous)
                    .fill(Color(.secondarySystemGroupedBackground))
            )
            .overlay(
                RoundedRectangle(cornerRadius: 12, style: .continuous)
                    .strokeBorder(selected ? AppTheme.teal : Color.clear, lineWidth: 2)
            )
        }
        .buttonStyle(.plain)
    }
}
