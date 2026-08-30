package ro.dateconta.facturare.ui.clients

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
import androidx.compose.runtime.LaunchedEffect
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
import ro.dateconta.facturare.core.db.LocalClientEntity
import java.util.UUID

@Composable
fun ClientEditorScreen(appState: AppState, clientUuid: String?, onBack: () -> Unit) {
    var name by remember { mutableStateOf("") }
    var cui by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val companyId = appState.auth.currentCompanyId ?: 0
    val isNew = clientUuid == "new"
    val uuid = remember { if (isNew) UUID.randomUUID().toString() else clientUuid!! }

    LaunchedEffect(clientUuid) {
        if (!isNew && clientUuid != null) {
            appState.db.clientDao().getByUuid(clientUuid)?.let {
                name = it.name
                cui = it.cui.orEmpty()
                email = it.email.orEmpty()
                phone = it.phone.orEmpty()
                address = it.address.orEmpty()
            }
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        Text(if (isNew) "Client nou" else "Editează client", fontWeight = FontWeight.Bold)
        OutlinedTextField(value = name, onValueChange = { name = it }, label = { Text("Nume") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = cui, onValueChange = { cui = it }, label = { Text("CUI") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Email") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = phone, onValueChange = { phone = it }, label = { Text("Telefon") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = address, onValueChange = { address = it }, label = { Text("Adresă") }, modifier = Modifier.fillMaxWidth())

        OutlinedButton(onClick = {
            scope.launch {
                try {
                    val result: Map<String, Map<String, String>> = appState.api.request(
                        "POST",
                        "clients/anaf-lookup",
                        body = mapOf("cui" to cui),
                    )
                    result["data"]?.get("name")?.let { name = it }
                    result["data"]?.get("address")?.let { address = it }
                } catch (e: Exception) {
                    message = e.message
                }
            }
        }, modifier = Modifier.fillMaxWidth()) { Text("Caută în ANAF") }

        Button(onClick = {
            scope.launch {
                val existing = appState.db.clientDao().getByUuid(uuid)
                val entity = (existing ?: LocalClientEntity(clientUUID = uuid, companyId = companyId, name = name)).copy(
                    name = name,
                    cui = cui.ifBlank { null },
                    email = email.ifBlank { null },
                    phone = phone.ifBlank { null },
                    address = address.ifBlank { null },
                    pendingSync = true,
                )
                appState.db.clientDao().upsert(entity)
                appState.sync.enqueue(
                    entity = "client",
                    action = if (existing?.serverId != null) "update" else "create",
                    clientUUID = uuid,
                    serverId = existing?.serverId,
                    payload = mapOf(
                        "name" to name,
                        "type" to "company",
                        "cui" to cui,
                        "email" to email,
                        "phone" to phone,
                        "address" to address,
                    ),
                )
                onBack()
            }
        }, modifier = Modifier.fillMaxWidth()) { Text("Salvează") }
        message?.let { Text(it) }
    }
}
