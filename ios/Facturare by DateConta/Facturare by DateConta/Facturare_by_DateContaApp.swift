//
//  Facturare_by_DateContaApp.swift
//  Facturare by DateConta
//

import SwiftUI
import SwiftData

@main
struct Facturare_by_DateContaApp: App {
    @State private var auth = AuthStore()
    @State private var sync = SyncEngine()
    @State private var subscription = SubscriptionStore()

    var sharedModelContainer: ModelContainer = {
        Self.makeModelContainer()
    }()

    init() {
#if os(iOS)
        BackgroundSyncScheduler.shared.register()
#endif
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environment(auth)
                .environment(sync)
                .environment(subscription)
                .tint(AppTheme.accent)
                .onAppear {
                    BackgroundSyncBridge.auth = auth
                    BackgroundSyncBridge.sync = sync
                }
        }
        .modelContainer(sharedModelContainer)
    }

    /// Deschide SwiftData; dacă migrația eșuează (schema veche pe simulator/device),
    /// șterge store-ul local și recreează — datele offline se resincronizează de pe server.
    private static func makeModelContainer() -> ModelContainer {
        let schema = Schema([
            LocalClient.self,
            LocalProduct.self,
            LocalDocument.self,
            LocalSeries.self,
            LocalPayment.self,
            OutboxOperation.self,
            SyncMeta.self,
        ])
        let config = ModelConfiguration(schema: schema, isStoredInMemoryOnly: false)

        do {
            return try ModelContainer(for: schema, configurations: [config])
        } catch {
            Self.destroyPersistentStore(at: config.url)
            do {
                return try ModelContainer(for: schema, configurations: [config])
            } catch {
                fatalError("SwiftData container failed after store reset: \(error)")
            }
        }
    }

    private static func destroyPersistentStore(at url: URL) {
        let fm = FileManager.default
        let directory = url.deletingLastPathComponent()
        let base = url.deletingPathExtension().lastPathComponent
        guard let items = try? fm.contentsOfDirectory(at: directory, includingPropertiesForKeys: nil) else {
            try? fm.removeItem(at: url)
            return
        }
        for item in items where item.lastPathComponent.hasPrefix(base) {
            try? fm.removeItem(at: item)
        }
    }
}
