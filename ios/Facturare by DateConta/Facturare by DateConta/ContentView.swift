//
//  ContentView.swift
//  Facturare by DateConta
//

import SwiftUI

struct ContentView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(SyncEngine.self) private var sync
    @Environment(SubscriptionStore.self) private var subscription
    @Environment(\.scenePhase) private var scenePhase

    var body: some View {
        Group {
            if auth.isAuthenticated {
                if subscription.hasAccess {
                    RootShellView()
                } else {
                    PaywallView()
                }
            } else {
                LoginView()
            }
        }
        .animation(.easeInOut(duration: 0.25), value: auth.isAuthenticated)
        .animation(.easeInOut(duration: 0.25), value: subscription.hasAccess)
        .task(id: auth.isAuthenticated) {
            guard auth.isAuthenticated else { return }
            await subscription.start()
        }
        .onChange(of: scenePhase) { _, phase in
#if os(iOS)
            BackgroundSyncBridge.auth = auth
            BackgroundSyncBridge.sync = sync
            switch phase {
            case .background:
                BackgroundSyncScheduler.shared.appDidEnterBackground()
            case .active:
                if auth.isAuthenticated {
                    Task {
                        await subscription.refreshLocalEntitlements()
                        await subscription.refreshServerStatus()
                        if subscription.hasAccess, auth.currentCompanyId != nil {
                            await sync.syncNow()
                        }
                    }
                }
            default:
                break
            }
#endif
        }
    }
}

#Preview {
    ContentView()
        .environment(AuthStore())
        .environment(SyncEngine())
        .environment(SubscriptionStore())
}
