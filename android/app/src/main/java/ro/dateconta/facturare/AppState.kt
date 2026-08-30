package ro.dateconta.facturare

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import ro.dateconta.facturare.core.sync.SyncStatus

class AppState(val container: AppContainer) {
    val scope = CoroutineScope(SupervisorJob() + Dispatchers.Main.immediate)

    val auth get() = container.auth
    val sync get() = container.sync
    val subscription get() = container.subscription
    val api get() = container.api
    val db get() = container.database

    var isAuthenticated by mutableStateOf(auth.isAuthenticated)
        private set

    var hasAccess by mutableStateOf(subscription.hasAccess)
        private set

    var syncStatus by mutableStateOf<SyncStatus>(SyncStatus.Idle)
        private set

    var pendingCount by mutableIntStateOf(0)
        private set

    val pendingCountFlow = db.outboxDao().observeCount()
        .stateIn(scope, SharingStarted.WhileSubscribed(5000), 0)

    init {
        scope.launch {
            pendingCountFlow.collect { pendingCount = it }
        }
    }

    fun refreshAuthState() {
        isAuthenticated = auth.isAuthenticated
        hasAccess = subscription.hasAccess
    }

    fun onForeground() {
        scope.launch {
            if (auth.isAuthenticated) {
                subscription.refresh()
                refreshAuthState()
                if (subscription.hasAccess && auth.currentCompanyId != null) {
                    syncNow()
                }
            }
        }
    }

    fun syncNow() {
        scope.launch {
            sync.syncNow()
            syncStatus = sync.status
            pendingCount = sync.pendingCount
        }
    }

    suspend fun login(email: String, password: String): Boolean {
        val ok = auth.login(email, password)
        if (ok) {
            subscription.refresh()
            refreshAuthState()
            if (hasAccess) syncNow()
        }
        return ok
    }

    suspend fun register(name: String, email: String, password: String, confirm: String): Boolean {
        val ok = auth.register(name, email, password, confirm)
        if (ok) {
            subscription.refresh()
            refreshAuthState()
            if (hasAccess) syncNow()
        }
        return ok
    }

    suspend fun logout() {
        auth.logout()
        refreshAuthState()
    }
}
