package ro.dateconta.facturare.ui.catalog

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.ui.clients.ClientEditorScreen
import ro.dateconta.facturare.ui.products.ProductEditorScreen

@Composable
fun CatalogScreen(appState: AppState) {
    var tab by remember { mutableIntStateOf(0) }
    var editingClientUuid by remember { mutableStateOf<String?>(null) }
    var editingProductUuid by remember { mutableStateOf<String?>(null) }

    when {
        editingClientUuid != null -> ClientEditorScreen(appState, editingClientUuid, onBack = { editingClientUuid = null })
        editingProductUuid != null -> ProductEditorScreen(appState, editingProductUuid, onBack = { editingProductUuid = null })
        else -> {
            Column(Modifier.fillMaxSize()) {
                Text("Catalog", style = androidx.compose.material3.MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
                TabRow(selectedTabIndex = tab) {
                    Tab(selected = tab == 0, onClick = { tab = 0 }, text = { Text("Clienți") })
                    Tab(selected = tab == 1, onClick = { tab = 1 }, text = { Text("Produse") })
                }
                when (tab) {
                    0 -> ClientsList(appState, onOpen = { editingClientUuid = it }, onNew = { editingClientUuid = "new" })
                    1 -> ProductsList(appState, onOpen = { editingProductUuid = it }, onNew = { editingProductUuid = "new" })
                }
            }
        }
    }
}

@Composable
private fun ClientsList(appState: AppState, onOpen: (String) -> Unit, onNew: () -> Unit) {
    val clients by appState.db.clientDao().observeAll().collectAsState(initial = emptyList())
    LazyColumn {
        item {
            Card(Modifier.fillMaxWidth().padding(8.dp).clickable(onClick = onNew)) {
                Text("+ Client nou", Modifier.padding(16.dp), fontWeight = FontWeight.SemiBold)
            }
        }
        items(clients.size) { i ->
            val c = clients[i]
            Card(Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp).clickable { onOpen(c.clientUUID) }) {
                Column(Modifier.padding(12.dp)) {
                    Text(c.name, fontWeight = FontWeight.SemiBold)
                    c.cui?.let { Text("CUI: $it") }
                    if (c.pendingSync) Text("În așteptare sync", color = androidx.compose.ui.graphics.Color(0xFFC45C10))
                }
            }
        }
    }
}

@Composable
private fun ProductsList(appState: AppState, onOpen: (String) -> Unit, onNew: () -> Unit) {
    val products by appState.db.productDao().observeAll().collectAsState(initial = emptyList())
    LazyColumn {
        item {
            Card(Modifier.fillMaxWidth().padding(8.dp).clickable(onClick = onNew)) {
                Text("+ Produs nou", Modifier.padding(16.dp), fontWeight = FontWeight.SemiBold)
            }
        }
        items(products.size) { i ->
            val p = products[i]
            Card(Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp).clickable { onOpen(p.clientUUID) }) {
                Column(Modifier.padding(12.dp)) {
                    Text(p.name, fontWeight = FontWeight.SemiBold)
                    Text("${p.price} RON · TVA ${p.vatRate}%")
                    if (p.pendingSync) Text("În așteptare sync", color = androidx.compose.ui.graphics.Color(0xFFC45C10))
                }
            }
        }
    }
}
