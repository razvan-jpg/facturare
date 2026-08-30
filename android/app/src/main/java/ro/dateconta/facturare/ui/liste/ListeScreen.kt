package ro.dateconta.facturare.ui.liste

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.ui.documents.DocumentDetailScreen

@Composable
fun ListeScreen(appState: AppState) {
    var selectedType by remember { mutableStateOf("invoice") }
    var selectedUuid by remember { mutableStateOf<String?>(null) }

    val documents by appState.db.documentDao().observeByType(selectedType)
        .collectAsState(initial = emptyList())

    if (selectedUuid != null) {
        DocumentDetailScreen(appState, selectedUuid!!, onBack = { selectedUuid = null })
        return
    }

    Column(Modifier.fillMaxSize()) {
        Text("Liste documente", style = androidx.compose.material3.MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
        val types = listOf(
            "invoice" to "Facturi",
            "proforma" to "Proforme",
            "delivery" to "Avize",
            "receipt" to "Chitanțe",
            "storno" to "Storno",
            "credit_note" to "Note credit",
        )
        androidx.compose.foundation.layout.Row(Modifier.padding(vertical = 8.dp)) {
            types.forEach { (type, label) ->
                FilterChip(
                    selected = selectedType == type,
                    onClick = { selectedType = type },
                    label = { Text(label) },
                    modifier = Modifier.padding(end = 6.dp),
                )
            }
        }

        LazyColumn {
            items(documents.size) { index ->
                val doc = documents[index]
                Card(
                    Modifier
                        .fillMaxWidth()
                        .padding(vertical = 4.dp)
                        .clickable { selectedUuid = doc.clientUUID },
                ) {
                    Column(Modifier.padding(12.dp)) {
                        Text(doc.numberFull ?: "Draft", fontWeight = FontWeight.SemiBold)
                        Text(doc.clientName ?: "—")
                        Text("${doc.status} · ${doc.total} RON")
                        if (doc.pendingSync) Text("În așteptare sync", color = androidx.compose.ui.graphics.Color(0xFFC45C10))
                    }
                }
            }
        }
    }
}
