package ro.dateconta.facturare.ui.recurring

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.OutlinedButton
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
import ro.dateconta.facturare.core.api.DataEnvelope
import ro.dateconta.facturare.core.api.ApiRecurring

@Composable
fun RecurringListScreen(appState: AppState, onBack: () -> Unit) {
    var items by remember { mutableStateOf<List<ApiRecurring>>(emptyList()) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val response: DataEnvelope<List<ApiRecurring>> = appState.api.request("GET", "recurring")
            items = response.data
        } catch (e: Exception) {
            error = e.message
        }
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        Text("Facturi recurente", fontWeight = FontWeight.Bold, modifier = Modifier.padding(vertical = 8.dp))
        error?.let { Text(it, color = androidx.compose.ui.graphics.Color.Red) }
        LazyColumn {
            items(items) { item ->
                Card(Modifier.padding(vertical = 4.dp)) {
                    Column(Modifier.padding(12.dp)) {
                        Text(item.name, fontWeight = FontWeight.SemiBold)
                        Text(item.clientName ?: "—")
                        Text("Următoarea: ${item.nextRunDate ?: "—"}")
                    }
                }
            }
        }
    }
}
