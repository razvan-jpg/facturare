import Foundation
#if canImport(UIKit)
import UIKit
import BackgroundTasks
#endif

enum BackgroundSyncIDs {
    static let refresh = "com.dateconta.Facturare-by-DateConta.Facturare-by-DateConta.sync.refresh"
    static let processing = "com.dateconta.Facturare-by-DateConta.Facturare-by-DateConta.sync.processing"
}

/// Păstrează referințe la sync/auth pentru task-urile de fundal (iOS).
@MainActor
enum BackgroundSyncBridge {
    static weak var sync: SyncEngine?
    static weak var auth: AuthStore?
}

#if os(iOS)
@MainActor
final class BackgroundSyncScheduler {
    static let shared = BackgroundSyncScheduler()

    private var didRegister = false
    private var backgroundTaskID = UIBackgroundTaskIdentifier.invalid
    private var isRunningBackgroundSync = false

    private init() {}

    func register() {
        guard !didRegister else { return }
        didRegister = true

        BGTaskScheduler.shared.register(forTaskWithIdentifier: BackgroundSyncIDs.refresh, using: nil) { task in
            guard let task = task as? BGAppRefreshTask else { return }
            Task { @MainActor in
                await BackgroundSyncScheduler.shared.handleRefresh(task)
            }
        }

        BGTaskScheduler.shared.register(forTaskWithIdentifier: BackgroundSyncIDs.processing, using: nil) { task in
            guard let task = task as? BGProcessingTask else { return }
            Task { @MainActor in
                await BackgroundSyncScheduler.shared.handleProcessing(task)
            }
        }
    }

    /// Apelată când aplicația trece în fundal (Home / app switcher).
    func appDidEnterBackground() {
        guard BackgroundSyncBridge.auth?.isAuthenticated == true else { return }
        let sync = BackgroundSyncBridge.sync
        sync?.refreshPendingCount()

        scheduleRefresh()
        if (sync?.pendingCount ?? 0) > 0 || sync?.status == .syncing || sync?.status == .offline {
            scheduleProcessing()
        }

        Task {
            await runProtectedBackgroundSync()
        }
    }

    func scheduleRefresh() {
        let request = BGAppRefreshTaskRequest(identifier: BackgroundSyncIDs.refresh)
        request.earliestBeginDate = Date(timeIntervalSinceNow: 60)
        try? BGTaskScheduler.shared.submit(request)
    }

    func scheduleProcessing() {
        let request = BGProcessingTaskRequest(identifier: BackgroundSyncIDs.processing)
        request.requiresNetworkConnectivity = true
        request.requiresExternalPower = false
        request.earliestBeginDate = Date(timeIntervalSinceNow: 30)
        try? BGTaskScheduler.shared.submit(request)
    }

    private func handleRefresh(_ task: BGAppRefreshTask) async {
        scheduleRefresh()
        var expired = false
        task.expirationHandler = {
            expired = true
            Task { @MainActor in
                BackgroundSyncScheduler.shared.scheduleProcessing()
            }
        }
        await runProtectedBackgroundSync()
        let pending = BackgroundSyncBridge.sync?.pendingCount ?? 0
        task.setTaskCompleted(success: !expired && pending == 0)
        if pending > 0 { scheduleProcessing() }
    }

    private func handleProcessing(_ task: BGProcessingTask) async {
        var expired = false
        task.expirationHandler = {
            expired = true
            Task { @MainActor in
                BackgroundSyncScheduler.shared.scheduleProcessing()
            }
        }
        await runProtectedBackgroundSync()
        let pending = BackgroundSyncBridge.sync?.pendingCount ?? 0
        let done = !expired && pending == 0
        task.setTaskCompleted(success: done)
        if !done { scheduleProcessing() }
    }

    /// Cere timp de execuție în fundal ca sync-ul outbox + pull să poată termina.
    func runProtectedBackgroundSync() async {
        guard !isRunningBackgroundSync else { return }
        guard BackgroundSyncBridge.auth?.isAuthenticated == true else { return }
        guard let sync = BackgroundSyncBridge.sync else { return }

        isRunningBackgroundSync = true
        defer { isRunningBackgroundSync = false }

        if backgroundTaskID != .invalid {
            UIApplication.shared.endBackgroundTask(backgroundTaskID)
            backgroundTaskID = .invalid
        }

        backgroundTaskID = UIApplication.shared.beginBackgroundTask(withName: "DateContaSync") { [weak self] in
            Task { @MainActor in
                guard let self else { return }
                if self.backgroundTaskID != .invalid {
                    UIApplication.shared.endBackgroundTask(self.backgroundTaskID)
                    self.backgroundTaskID = .invalid
                }
                self.scheduleProcessing()
            }
        }

        await sync.syncNow()
        sync.refreshPendingCount()
        if sync.pendingCount > 0 {
            scheduleProcessing()
        }

        if backgroundTaskID != .invalid {
            UIApplication.shared.endBackgroundTask(backgroundTaskID)
            backgroundTaskID = .invalid
        }
    }
}
#endif
