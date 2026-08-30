package ro.dateconta.facturare.ui.reports

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.core.api.ReportSummary
import ro.dateconta.facturare.core.util.DateFormats

@Composable
fun ReportsScreen(appState: AppState) {
    var fromDate by remember { mutableStateOf("") }
    var toDate by remember { mutableStateOf("") }
    var summary by remember { mutableStateOf<ReportSummary?>(null) }
    var partners by remember { mutableStateOf<String?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Column(Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text("Rapoarte", fontWeight = FontWeight.Bold)
        OutlinedTextField(value = fromDate, onValueChange = { fromDate = it }, label = { Text("De la (YYYY-MM-DD)") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = toDate, onValueChange = { toDate = it }, label = { Text("Până la (YYYY-MM-DD)") }, modifier = Modifier.fillMaxWidth())

        Button(onClick = {
            scope.launch {
                try {
                    summary = appState.api.request(
                        "GET",
                        "reports/summary",
                        query = buildMap {
                            if (fromDate.isNotBlank()) put("from", fromDate)
                            if (toDate.isNotBlank()) put("to", toDate)
                        },
                    )
                } catch (e: Exception) {
                    error = e.message
                }
            }
        }, modifier = Modifier.fillMaxWidth()) { Text("Sumar vânzări / încasări") }

        summary?.let {
            Text("Facturat: ${DateFormats.formatMoney(it.invoicesTotal ?: 0.0)}")
            Text("Încasat: ${DateFormats.formatMoney(it.paymentsTotal ?: 0.0)}")
        }

        Button(onClick = {
            scope.launch {
                try {
                    partners = appState.api.rawRequest(
                        "GET",
                        "reports/partners-balance",
                        query = buildMap {
                            if (fromDate.isNotBlank()) put("from", fromDate)
                            if (toDate.isNotBlank()) put("to", toDate)
                        },
                    )
                } catch (e: Exception) {
                    error = e.message
                }
            }
        }, modifier = Modifier.fillMaxWidth()) { Text("Sold parteneri") }

        partners?.let { Text(it.take(2000)) }
        error?.let { Text(it, color = androidx.compose.ui.graphics.Color.Red) }
    }
}
