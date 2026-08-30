package ro.dateconta.facturare.ui.payments

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
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
import ro.dateconta.facturare.core.db.LocalPaymentEntity
import ro.dateconta.facturare.core.util.DateFormats
import java.util.UUID

@Composable
fun PaymentCreateScreen(appState: AppState, onBack: () -> Unit) {
    var documentId by remember { mutableStateOf("") }
    var amount by remember { mutableStateOf("") }
    var method by remember { mutableStateOf("op") }
    val scope = rememberCoroutineScope()
    val companyId = appState.auth.currentCompanyId ?: 0

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        Text("Încasare nouă", fontWeight = FontWeight.Bold)
        OutlinedTextField(value = documentId, onValueChange = { documentId = it }, label = { Text("ID document (server)") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = amount, onValueChange = { amount = it }, label = { Text("Sumă") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = method, onValueChange = { method = it }, label = { Text("Metodă (op/cash/card)") }, modifier = Modifier.fillMaxWidth())

        Button(onClick = {
            scope.launch {
                val uuid = UUID.randomUUID().toString()
                val paidAt = DateFormats.today()
                val entity = LocalPaymentEntity(
                    clientUUID = uuid,
                    companyId = companyId,
                    documentServerId = documentId.toIntOrNull(),
                    amount = amount.toDoubleOrNull() ?: 0.0,
                    method = method,
                    paidAt = paidAt,
                    pendingSync = true,
                )
                appState.db.paymentDao().upsert(entity)
                appState.sync.enqueue(
                    entity = "payment",
                    action = "create",
                    clientUUID = uuid,
                    payload = mapOf(
                        "document_id" to documentId.toIntOrNull(),
                        "amount" to entity.amount,
                        "method" to method,
                        "paid_at" to paidAt,
                    ),
                )
                onBack()
            }
        }, modifier = Modifier.fillMaxWidth()) { Text("Salvează încasare") }
    }
}

@Composable
fun PaymentsListScreen(appState: AppState) {
    val payments by appState.db.paymentDao().observeAll().collectAsState(initial = emptyList())
    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Text("Încasări", fontWeight = FontWeight.Bold)
        payments.forEach { p ->
            Text("${p.paidAt}: ${p.amount} RON (${p.method})")
        }
    }
}