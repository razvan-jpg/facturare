import SwiftUI

/// Comportament comun pentru liste offline (Facturi, Clienți, Produse, Încasări):
/// pull-to-refresh, empty state, sync automat dacă lista firmei e goală.
struct CompanySyncedListModifier: ViewModifier {
    @Environment(SyncEngine.self) private var sync
    @Environment(AuthStore.self) private var auth

    let isEmpty: Bool
    let emptyTitle: String
    let emptySystemImage: String

    func body(content: Content) -> some View {
        content
            .overlay {
                if isEmpty {
                    ContentUnavailableView {
                        Label(emptyTitle, systemImage: emptySystemImage)
                    } description: {
                        Text("Trage în jos ca să sincronizezi datele firmei curente.")
                    }
                }
            }
            .refreshable {
                await sync.syncNow()
            }
            .task(id: auth.currentCompanyId) {
                // Liste goale după schimbarea firmei: forțează pull (cursor per firmă).
                guard isEmpty else { return }
                await sync.syncNow()
            }
    }
}

extension View {
    func companySyncedList(
        isEmpty: Bool,
        emptyTitle: String,
        emptySystemImage: String = "tray"
    ) -> some View {
        modifier(CompanySyncedListModifier(
            isEmpty: isEmpty,
            emptyTitle: emptyTitle,
            emptySystemImage: emptySystemImage
        ))
    }
}
