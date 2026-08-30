package ro.dateconta.facturare.ui.products

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
import ro.dateconta.facturare.core.db.LocalProductEntity
import java.util.UUID

@Composable
fun ProductEditorScreen(appState: AppState, productUuid: String?, onBack: () -> Unit) {
    var name by remember { mutableStateOf("") }
    var price by remember { mutableStateOf("0") }
    var vat by remember { mutableStateOf("21") }
    var unit by remember { mutableStateOf("buc") }
    val scope = rememberCoroutineScope()
    val companyId = appState.auth.currentCompanyId ?: 0
    val isNew = productUuid == "new"
    val uuid = remember { if (isNew) UUID.randomUUID().toString() else productUuid!! }

    LaunchedEffect(productUuid) {
        if (!isNew && productUuid != null) {
            appState.db.productDao().getByUuid(productUuid)?.let {
                name = it.name
                price = it.price.toString()
                vat = it.vatRate.toString()
                unit = it.unit
            }
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        Text(if (isNew) "Produs nou" else "Editează produs", fontWeight = FontWeight.Bold)
        OutlinedTextField(value = name, onValueChange = { name = it }, label = { Text("Nume") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = price, onValueChange = { price = it }, label = { Text("Preț") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = vat, onValueChange = { vat = it }, label = { Text("TVA %") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = unit, onValueChange = { unit = it }, label = { Text("Unitate") }, modifier = Modifier.fillMaxWidth())

        Button(onClick = {
            scope.launch {
                val existing = appState.db.productDao().getByUuid(uuid)
                val entity = (existing ?: LocalProductEntity(clientUUID = uuid, companyId = companyId, name = name)).copy(
                    name = name,
                    price = price.toDoubleOrNull() ?: 0.0,
                    vatRate = vat.toDoubleOrNull() ?: 21.0,
                    unit = unit,
                    pendingSync = true,
                )
                appState.db.productDao().upsert(entity)
                appState.sync.enqueue(
                    entity = "product",
                    action = if (existing?.serverId != null) "update" else "create",
                    clientUUID = uuid,
                    serverId = existing?.serverId,
                    payload = mapOf(
                        "name" to name,
                        "type" to "service",
                        "price" to entity.price,
                        "vat_rate" to entity.vatRate,
                        "unit" to unit,
                        "active" to true,
                    ),
                )
                onBack()
            }
        }, modifier = Modifier.fillMaxWidth()) { Text("Salvează") }
    }
}
