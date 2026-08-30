package ro.dateconta.facturare.ui.subscription

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.core.api.ApiConfig
import ro.dateconta.facturare.ui.theme.AppTheme

@Composable
fun PaywallScreen(appState: AppState) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Text("Acces expirat", fontSize = 28.sp, fontWeight = FontWeight.Bold, color = AppTheme.Deep)
        Spacer(Modifier.height(12.dp))
        Text(
            "Abonamentul tău web a expirat. Reactivează accesul din aplicația web DateConta Facturare.",
            textAlign = TextAlign.Center,
            color = AppTheme.Deep.copy(alpha = 0.8f),
        )
        appState.subscription.accessLabel?.let {
            Spacer(Modifier.height(8.dp))
            Text(it, color = AppTheme.Warm, textAlign = TextAlign.Center)
        }
        Spacer(Modifier.height(24.dp))
        Button(
            onClick = {
                context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("${ApiConfig.WEB_BASE_URL}/billing")))
            },
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text("Deschide factura.dateconta.ro")
        }
        OutlinedButton(
            onClick = { scope.launch { appState.subscription.refresh(); appState.refreshAuthState() } },
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text("Reîmprospătează status")
        }
        TextButton(onClick = { scope.launch { appState.logout() } }) {
            Text("Deconectare")
        }
        if (appState.subscription.isInFreePeriod) {
            Spacer(Modifier.height(16.dp))
            Text(
                "Perioadă gratuită activă până la 31.03.2027",
                fontSize = 13.sp,
                color = AppTheme.Accent,
            )
        }
    }
}
