package ro.dateconta.facturare.ui.dashboard

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.core.api.DashboardResponse
import ro.dateconta.facturare.core.util.DateFormats
import ro.dateconta.facturare.ui.theme.AppTheme

@Composable
fun DashboardScreen(appState: AppState, key: Int = 0) {
    var dashboard by remember(key) { mutableStateOf<DashboardResponse?>(null) }
    var loading by remember(key) { mutableStateOf(true) }
    var error by remember(key) { mutableStateOf<String?>(null) }

    LaunchedEffect(key, appState.auth.currentCompanyId) {
        loading = true
        error = null
        try {
            dashboard = appState.api.request("GET", "dashboard")
        } catch (e: Exception) {
            error = e.message
        } finally {
            loading = false
        }
    }

    if (loading) {
        Column(Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center) {
            CircularProgressIndicator(modifier = Modifier.padding(24.dp))
        }
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Text(
                appState.auth.currentCompany?.name ?: "Panou",
                style = androidx.compose.material3.MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = AppTheme.Deep,
            )
            (dashboard?.accessLabel ?: appState.auth.user?.accessLabel)?.let {
                Text(it, color = androidx.compose.ui.graphics.Color.Gray)
            }
            error?.let { Text(it, color = androidx.compose.ui.graphics.Color.Red) }
        }

        dashboard?.stats?.let { stats ->
            item { StatCard("De încasat de la clienți", DateFormats.formatMoney(stats.clientsReceivableToday ?: 0.0), "la data de azi", emphasize = true) }
            item { StatCard("Facturat azi", DateFormats.formatMoney(stats.invoicesTodayTotal ?: 0.0)) }
            item { StatCard("Facturat luna aceasta", DateFormats.formatMoney(stats.invoicesMonthTotal)) }
            item { StatCard("Neplătite / Draft-uri", "${stats.unpaidCount ?: 0} / ${stats.draftsCount ?: 0}") }
            item { StatCard("Încasat azi", DateFormats.formatMoney(stats.paymentsTodayTotal ?: 0.0)) }
            item { StatCard("Încasat luna aceasta", DateFormats.formatMoney(stats.paymentsMonthTotal)) }
        }

        item {
            Text("Facturi neplătite", fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 8.dp))
        }
        items(dashboard?.unpaid.orEmpty()) { doc ->
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(12.dp)) {
                    Text(doc.numberFull ?: "#${doc.id}", fontWeight = FontWeight.SemiBold)
                    Text(doc.clientName ?: "—")
                    Text(DateFormats.formatMoney(doc.total ?: 0.0))
                }
            }
        }

        item {
            Text("Draft-uri recente", fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 8.dp))
        }
        items(dashboard?.drafts ?: dashboard?.recentDocuments.orEmpty()) { doc ->
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(12.dp)) {
                    Text(doc.numberFull ?: "Draft #${doc.id}", fontWeight = FontWeight.SemiBold)
                    Text(doc.clientName ?: "—")
                }
            }
        }
    }
}

@Composable
private fun StatCard(title: String, value: String, caption: String? = null, emphasize: Boolean = false) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = androidx.compose.material3.CardDefaults.cardColors(
            containerColor = if (emphasize) AppTheme.Accent.copy(alpha = 0.08f) else androidx.compose.ui.graphics.Color.White,
        ),
    ) {
        Column(Modifier.padding(14.dp)) {
            Text(title, color = AppTheme.Deep.copy(alpha = 0.7f))
            Text(value, fontWeight = FontWeight.Bold, color = AppTheme.Deep)
            caption?.let { Text(it, color = androidx.compose.ui.graphics.Color.Gray) }
        }
    }
}
