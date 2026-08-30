package ro.dateconta.facturare.ui.help

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
import ro.dateconta.facturare.core.api.HelpSection
import ro.dateconta.facturare.core.api.HelpSectionResponse
import ro.dateconta.facturare.core.api.WhatsNewResponse

@Composable
fun HelpScreen(appState: AppState) {
    var sections by remember { mutableStateOf<List<HelpSection>>(emptyList()) }
    var selectedKey by remember { mutableStateOf<String?>(null) }
    var html by remember { mutableStateOf<String?>(null) }
    var showWhatsNew by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        try {
            val response: DataEnvelope<List<HelpSection>> = appState.api.request("GET", "help")
            sections = response.data
        } catch (_: Exception) {
        }
    }

    LaunchedEffect(selectedKey) {
        selectedKey?.let { key ->
            try {
                val response: HelpSectionResponse = appState.api.request("GET", "help/$key")
                html = wrapHtml(response.title, response.html)
            } catch (_: Exception) {
            }
        }
    }

    if (showWhatsNew) {
        WhatsNewView(appState, onBack = { showWhatsNew = false })
        return
    }

    if (html != null) {
        Column(Modifier.fillMaxSize()) {
            OutlinedButton(onClick = { html = null; selectedKey = null }) { Text("Înapoi") }
            HtmlView(html!!)
        }
        return
    }

    LazyColumn(Modifier.fillMaxSize().padding(16.dp)) {
        item {
            Text("Ajutor", fontWeight = FontWeight.Bold)
            Card(Modifier.fillMaxWidth().padding(vertical = 8.dp).clickable { showWhatsNew = true }) {
                Text("Ce este nou…", Modifier.padding(16.dp), fontWeight = FontWeight.Medium)
            }
        }
        items(sections) { section ->
            Card(Modifier.fillMaxWidth().padding(vertical = 4.dp).clickable { selectedKey = section.key }) {
                Text(section.title, Modifier.padding(16.dp))
            }
        }
    }
}

@Composable
private fun WhatsNewView(appState: AppState, onBack: () -> Unit) {
    var entries by remember { mutableStateOf<WhatsNewResponse?>(null) }
    LaunchedEffect(Unit) {
        entries = appState.api.request("GET", "help/ce-este-nou")
    }
    Column(Modifier.fillMaxSize().padding(16.dp)) {
        OutlinedButton(onClick = onBack) { Text("Înapoi") }
        entries?.entries?.forEach { entry ->
            Text("${entry.version} — ${entry.title ?: ""}", fontWeight = FontWeight.SemiBold)
            entry.notes.forEach { Text("• $it") }
        }
    }
}

@Composable
private fun HtmlView(html: String) {
    AndroidView(
        factory = { context ->
            WebView(context).apply {
                webViewClient = WebViewClient()
                settings.javaScriptEnabled = false
                loadDataWithBaseURL(null, html, "text/html", "UTF-8", null)
            }
        },
        modifier = Modifier.fillMaxSize(),
    )
}

private fun wrapHtml(title: String, body: String): String = """
    <html><head><meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>body{font-family:sans-serif;padding:16px;color:#0a3440;} h1{color:#0f766e;}</style>
    </head><body><h1>$title</h1>$body</body></html>
""".trimIndent()
