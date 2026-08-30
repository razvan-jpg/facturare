package ro.dateconta.facturare.ui.legal

import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.OutlinedButton
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
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.viewinterop.AndroidView
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.core.api.DataEnvelope
import ro.dateconta.facturare.core.api.LegalPage
import ro.dateconta.facturare.core.api.LegalPageResponse

@Composable
fun LegalScreen(appState: AppState) {
    var pages by remember { mutableStateOf<List<LegalPage>>(emptyList()) }
    var html by remember { mutableStateOf<String?>(null) }

    val scope = rememberCoroutineScope()

    LaunchedEffect(Unit) {
        try {
            val response: DataEnvelope<List<LegalPage>> = appState.api.request("GET", "legal", authorized = false)
            pages = response.data
        } catch (_: Exception) {
        }
    }

    if (html != null) {
        val htmlContent = html!!
        Column(Modifier.fillMaxSize()) {
            OutlinedButton(onClick = { html = null }) { Text("Înapoi") }
            AndroidView(
                factory = { context ->
                    WebView(context).apply {
                        webViewClient = WebViewClient()
                        loadDataWithBaseURL(null, htmlContent, "text/html", "UTF-8", null)
                    }
                },
                modifier = Modifier.fillMaxSize(),
            )
        }
        return
    }

    LazyColumn(Modifier.fillMaxSize().padding(16.dp)) {
        item { Text("Legal", fontWeight = FontWeight.Bold) }
        items(pages) { page ->
            Card(
                Modifier
                    .fillMaxWidth()
                    .padding(vertical = 4.dp)
                    .clickable {
                        scope.launch {
                            try {
                                val response: LegalPageResponse = appState.api.request(
                                    "GET",
                                    "legal/${page.key}",
                                    authorized = false,
                                )
                                html = "<html><body><h1>${response.title}</h1>${response.html}</body></html>"
                            } catch (_: Exception) {
                            }
                        }
                    },
            ) {
                Text(page.title, Modifier.padding(16.dp))
            }
        }
    }
}
