package ro.dateconta.facturare.ui.documents

import android.content.Intent
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import kotlinx.coroutines.launch
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.core.db.LocalDocumentEntity
import ro.dateconta.facturare.core.util.DateFormats
import java.io.File
import java.util.UUID

@Composable
fun DocumentDetailScreen(appState: AppState, clientUuid: String, onBack: () -> Unit) {
    var doc by remember { mutableStateOf<LocalDocumentEntity?>(null) }
    var message by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val context = LocalContext.current

    LaunchedEffect(clientUuid) {
        doc = appState.db.documentDao().getByUuid(clientUuid)
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        doc?.let { d ->
            Text(d.numberFull ?: "Document draft", fontWeight = FontWeight.Bold)
            Text("Client: ${d.clientName ?: "—"}")
            Text("Status: ${d.status}")
            Text("Total: ${DateFormats.formatMoney(d.total)}")
            d.efacturaStatus?.let { Text("e-Factura: $it") }

            if (d.serverId != null) {
                Button(onClick = {
                    scope.launch {
                        try {
                            val bytes = appState.api.downloadPdf(d.serverId!!)
                            val file = File(context.cacheDir, "pdf-${d.serverId}.pdf")
                            file.writeBytes(bytes)
                            val uri = FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)
                            context.startActivity(Intent(Intent.ACTION_VIEW).setDataAndType(uri, "application/pdf").addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION))
                        } catch (e: Exception) {
                            message = e.message
                        }
                    }
                }) { Text("Deschide PDF") }

                if (d.type == "invoice" && d.status == "issued") {
                    OutlinedButton(onClick = {
                        scope.launch {
                            try {
                                appState.api.request<Map<String, String>>("POST", "documents/${d.serverId}/efactura/send")
                                message = "Trimis către e-Factura"
                            } catch (e: Exception) {
                                message = e.message
                            }
                        }
                    }) { Text("Trimite e-Factura") }
                }
            }

            if (d.status == "draft" && !d.pendingIssue) {
                Button(onClick = {
                    scope.launch {
                        appState.sync.enqueue(
                            entity = "document",
                            action = "issue",
                            clientUUID = d.clientUUID,
                            serverId = d.serverId,
                        )
                        appState.db.documentDao().upsert(d.copy(pendingIssue = true))
                        message = "Emisere în coadă sync"
                    }
                }) { Text("Emite document") }
            }
        }
        message?.let { Text(it) }
    }
}

@Composable
fun DocumentEditorScreen(appState: AppState, type: String, onBack: () -> Unit) {
    var clientName by remember { mutableStateOf("") }
    var notes by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val companyId = appState.auth.currentCompanyId ?: 0

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        Text("Document nou ($type)", fontWeight = FontWeight.Bold)
        androidx.compose.material3.OutlinedTextField(value = clientName, onValueChange = { clientName = it }, label = { Text("Client") }, modifier = Modifier.fillMaxWidth())
        androidx.compose.material3.OutlinedTextField(value = notes, onValueChange = { notes = it }, label = { Text("Note") }, modifier = Modifier.fillMaxWidth())

        Button(onClick = {
            scope.launch {
                val uuid = UUID.randomUUID().toString()
                val itemsJson = """[{"name":"Serviciu","unit":"buc","quantity":1,"unit_price":0,"vat_rate":21}]"""
                val entity = LocalDocumentEntity(
                    clientUUID = uuid,
                    companyId = companyId,
                    type = type,
                    status = "draft",
                    issueDate = DateFormats.today(),
                    clientName = clientName.ifBlank { null },
                    notes = notes.ifBlank { null },
                    itemsJSON = itemsJson,
                    pendingSync = true,
                )
                appState.db.documentDao().upsert(entity)
                appState.sync.enqueue(
                    entity = "document",
                    action = "create",
                    clientUUID = uuid,
                    payload = mapOf(
                        "type" to type,
                        "issue_date" to DateFormats.today(),
                        "client_name" to clientName,
                        "notes" to notes,
                        "items" to listOf(
                            mapOf("name" to "Serviciu", "unit" to "buc", "quantity" to 1, "unit_price" to 0, "vat_rate" to 21),
                        ),
                    ),
                )
                message = "Salvat local — se sincronizează"
                onBack()
            }
        }, modifier = Modifier.fillMaxWidth()) {
            Text("Salvează draft")
        }
        message?.let { Text(it) }
    }
}
