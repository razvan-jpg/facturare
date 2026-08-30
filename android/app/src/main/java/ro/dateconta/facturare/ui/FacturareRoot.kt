package ro.dateconta.facturare.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.ui.auth.LoginScreen
import ro.dateconta.facturare.ui.shell.RootShellScreen
import ro.dateconta.facturare.ui.subscription.PaywallScreen

@Composable
fun FacturareRoot(appState: AppState) {
    val lifecycleOwner = LocalLifecycleOwner.current
    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME) {
                appState.onForeground()
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    LaunchedEffect(appState.isAuthenticated) {
        if (appState.isAuthenticated) {
            appState.subscription.refresh()
            appState.refreshAuthState()
        }
    }

    when {
        !appState.isAuthenticated -> LoginScreen(appState)
        !appState.hasAccess -> PaywallScreen(appState)
        else -> RootShellScreen(appState)
    }
}
