package ro.dateconta.facturare.ui.efactura

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.core.api.ApiConfig
import ro.dateconta.facturare.core.api.EfacturaStatusResponse
import androidx.compose.runtime.rememberCoroutineScope
import kotlinx.coroutines.launch

@Composable
fun EfacturaScreen(appState: AppState, onBack: () -> Unit) {
    var status by remember { mutableStateOf<EfacturaStatusResponse?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    LaunchedEffect(Unit) {
        try {
            status = appState.api.request("GET", "efactura/status")
        } catch (e: Exception) {
            error = e.message
        }
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        Text("e-Factura / SPV", fontWeight = FontWeight.Bold, modifier = Modifier.padding(vertical = 8.dp))
        status?.let {
            Text(if (it.authorized == true) "Autorizat ANAF" else "Neautorizat")
            it.message?.let { msg -> Text(msg) }
            if (it.authorized != true) {
                Button(onClick = {
                    scope.launch {
                        try {
                            val response: Map<String, String> = appState.api.request("GET", "efactura/oauth-url")
                            response["url"]?.let { url ->
                                context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                            }
                        } catch (e: Exception) {
                            error = e.message
                        }
                    }
                }) { Text("Autorizează SPV") }
            }
        }
        OutlinedButton(onClick = {
            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("${ApiConfig.WEB_BASE_URL}/setari/efactura")))
        }) { Text("Setări e-Factura (web)") }
        error?.let { Text(it, color = androidx.compose.ui.graphics.Color.Red) }
    }
}
