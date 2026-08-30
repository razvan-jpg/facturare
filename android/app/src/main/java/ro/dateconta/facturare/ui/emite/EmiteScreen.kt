package ro.dateconta.facturare.ui.emite

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.ui.documents.DocumentEditorScreen
import ro.dateconta.facturare.ui.payments.PaymentCreateScreen
import ro.dateconta.facturare.ui.recurring.RecurringListScreen

@Composable
fun EmiteScreen(appState: AppState) {
    var route by remember { mutableStateOf<EmiteRoute?>(null) }

    when (val r = route) {
        is EmiteRoute.NewDocument -> DocumentEditorScreen(appState, r.type, onBack = { route = null })
        EmiteRoute.NewPayment -> PaymentCreateScreen(appState, onBack = { route = null })
        EmiteRoute.Recurring -> RecurringListScreen(appState, onBack = { route = null })
        null -> EmiteHub(onSelect = { route = it })
    }
}

private sealed interface EmiteRoute {
    data class NewDocument(val type: String) : EmiteRoute
    data object NewPayment : EmiteRoute
    data object Recurring : EmiteRoute
}

@Composable
private fun EmiteHub(onSelect: (EmiteRoute) -> Unit) {
    val items = listOf(
        "Factură" to EmiteRoute.NewDocument("invoice"),
        "Proformă" to EmiteRoute.NewDocument("proforma"),
        "Aviz" to EmiteRoute.NewDocument("delivery"),
        "Chitanță" to EmiteRoute.NewDocument("receipt"),
        "Încasare" to EmiteRoute.NewPayment,
        "Facturi recurente" to EmiteRoute.Recurring,
    )

    LazyColumn(Modifier.fillMaxSize()) {
        item { Text("Emite", style = androidx.compose.material3.MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 12.dp)) }
        items(items.size) { index ->
            val (title, route) = items[index]
            Card(
                Modifier
                    .fillMaxWidth()
                    .padding(vertical = 6.dp)
                    .clickable { onSelect(route) },
            ) {
                Text(title, Modifier.padding(16.dp), fontWeight = FontWeight.Medium)
            }
        }
    }
}
