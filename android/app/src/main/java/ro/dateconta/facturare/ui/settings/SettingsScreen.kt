package ro.dateconta.facturare.ui.settings

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.BuildConfig
import ro.dateconta.facturare.core.api.ApiConfig
import ro.dateconta.facturare.core.api.EfacturaStatusResponse
import ro.dateconta.facturare.ui.efactura.EfacturaScreen

@Composable
fun SettingsScreen(appState: AppState, forceCreateCompany: Boolean = false) {
    var showDeleteDialog by remember { mutableStateOf(false) }
    var deletePassword by remember { mutableStateOf("") }
    var showEfactura by remember { mutableStateOf(false) }
    var companyName by remember { mutableStateOf("") }
    var companyCui by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val context = LocalContext.current

    if (showEfactura) {
        EfacturaScreen(appState, onBack = { showEfactura = false })
        return
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        Text("Setări", fontWeight = FontWeight.Bold)

        appState.auth.user?.let { user ->
            Text(user.name, fontWeight = FontWeight.SemiBold)
            Text(user.email)
            user.accessLabel?.let { Text(it) }
        }

        Text("Abonament", fontWeight = FontWeight.SemiBold)
        Text(appState.subscription.accessLabel ?: if (appState.subscription.isInFreePeriod) "Perioadă gratuită până la 31.03.2027" else "Verifică accesul în aplicația web")
        OutlinedButton(onClick = {
            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("${ApiConfig.WEB_BASE_URL}/billing")))
        }, modifier = Modifier.fillMaxWidth()) {
            Text("Gestionează abonament web")
        }

        Text("Sincronizare", fontWeight = FontWeight.SemiBold)
        OutlinedButton(onClick = { appState.syncNow() }, modifier = Modifier.fillMaxWidth()) {
            Text("Sincronizează acum")
        }
        Text("Status: ${appState.syncStatus.label}")

        if (!forceCreateCompany) {
            OutlinedButton(onClick = { showEfactura = true }, modifier = Modifier.fillMaxWidth()) {
                Text("e-Factura / SPV")
            }
            OutlinedButton(onClick = {
                scope.launch {
                    try {
                        val response: Map<String, String> = appState.api.request(
                            "POST",
                            "web-session",
                            body = mapOf("redirect" to "/setari"),
                        )
                        response["url"]?.let {
                            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(it)))
                        }
                    } catch (e: Exception) {
                        message = e.message
                    }
                }
            }, modifier = Modifier.fillMaxWidth()) {
                Text("Personalizare PDF (web)")
            }
        }

        if (forceCreateCompany || appState.auth.companies.isEmpty()) {
            Text("Firmă nouă", fontWeight = FontWeight.SemiBold)
            OutlinedTextField(value = companyName, onValueChange = { companyName = it }, label = { Text("Denumire") }, modifier = Modifier.fillMaxWidth())
            OutlinedTextField(value = companyCui, onValueChange = { companyCui = it }, label = { Text("CUI") }, modifier = Modifier.fillMaxWidth())
            OutlinedButton(onClick = {
                scope.launch {
                    try {
                        val result: Map<String, Map<String, String>> = appState.api.request(
                            "POST",
                            "companies/anaf-lookup",
                            body = mapOf("cui" to companyCui),
                        )
                        result["data"]?.get("name")?.let { companyName = it }
                    } catch (e: Exception) {
                        message = e.message
                    }
                }
            }, modifier = Modifier.fillMaxWidth()) { Text("Caută ANAF") }
            Button(onClick = {
                scope.launch {
                    try {
                        appState.api.request<Map<String, Any>>(
                            "POST",
                            "companies",
                            body = mapOf("name" to companyName, "cui" to companyCui),
                        )
                        appState.auth.refreshMe()
                        message = "Firmă creată"
                    } catch (e: Exception) {
                        message = e.message
                    }
                }
            }, modifier = Modifier.fillMaxWidth()) { Text("Creează firmă") }
        }

        OutlinedButton(onClick = { showDeleteDialog = true }, modifier = Modifier.fillMaxWidth()) {
            Text("Șterge contul")
        }
        OutlinedButton(onClick = { scope.launch { appState.logout() } }, modifier = Modifier.fillMaxWidth()) {
            Text("Deconectare")
        }

        Text(
            "Android ${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE}) · Web ${appState.auth.webAppVersion ?: "—"}",
        )
        message?.let { Text(it) }
    }

    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Șterge contul") },
            text = {
                Column {
                    Text("Confirmă cu parola. Acțiunea este ireversibilă.")
                    OutlinedTextField(
                        value = deletePassword,
                        onValueChange = { deletePassword = it },
                        label = { Text("Parolă") },
                        visualTransformation = PasswordVisualTransformation(),
                    )
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    scope.launch {
                        if (appState.auth.deleteAccount(deletePassword)) {
                            appState.refreshAuthState()
                            showDeleteDialog = false
                        }
                    }
                }) { Text("Șterge") }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteDialog = false }) { Text("Anulează") }
            },
        )
    }
}
