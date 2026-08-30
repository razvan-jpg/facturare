package ro.dateconta.facturare.ui.admin

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.OutlinedTextField
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
import ro.dateconta.facturare.core.api.AdminCompanyRow
import ro.dateconta.facturare.core.api.AdminStatsResponse

@Composable
fun AdminScreen(appState: AppState) {
    if (!appState.auth.isAdmin) {
        Text("Acces interzis", modifier = Modifier.padding(16.dp))
        return
    }

    var stats by remember { mutableStateOf<AdminStatsResponse?>(null) }
    var query by remember { mutableStateOf("") }
    var companies by remember { mutableStateOf<List<AdminCompanyRow>>(emptyList()) }

    LaunchedEffect(Unit) {
        stats = appState.api.request("GET", "admin/stats")
    }

    LaunchedEffect(query) {
        if (query.length >= 2) {
            val response: DataEnvelope<List<AdminCompanyRow>> = appState.api.request(
                "GET",
                "admin/companies",
                query = mapOf("q" to query),
            )
            companies = response.data
        }
    }

    LazyColumn(Modifier.fillMaxSize().padding(16.dp)) {
        item {
            Text("Admin", fontWeight = FontWeight.Bold)
            stats?.data?.let { d ->
                Text("Utilizatori: ${d.usersCount ?: 0}")
                Text("Firme: ${d.companiesCount ?: 0}")
                Text("Documente: ${d.documentsCount ?: 0}")
            }
            OutlinedTextField(
                value = query,
                onValueChange = { query = it },
                label = { Text("Caută firme") },
                modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
            )
        }
        items(companies) { company ->
            Card(Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                Column(Modifier.padding(12.dp)) {
                    Text(company.name, fontWeight = FontWeight.SemiBold)
                    company.cui?.let { Text("CUI: $it") }
                    company.promoCode?.let { Text("Promo: $it") }
                }
            }
        }
    }
}
