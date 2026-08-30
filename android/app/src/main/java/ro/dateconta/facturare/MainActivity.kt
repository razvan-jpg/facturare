package ro.dateconta.facturare

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.remember
import ro.dateconta.facturare.ui.FacturareRoot
import ro.dateconta.facturare.ui.theme.FacturareTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        val container = (application as FacturareApp).container
        setContent {
            FacturareTheme {
                val appState = remember { AppState(container) }
                FacturareRoot(appState = appState)
            }
        }
    }
}
