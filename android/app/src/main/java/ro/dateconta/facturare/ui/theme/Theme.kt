package ro.dateconta.facturare.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val LightColors = lightColorScheme(
    primary = AppTheme.Accent,
    onPrimary = Color.White,
    secondary = AppTheme.Teal,
    background = AppTheme.Mist,
    surface = Color.White,
    onBackground = AppTheme.Deep,
    onSurface = AppTheme.Deep,
)

private val DarkColors = darkColorScheme(
    primary = AppTheme.Accent,
    secondary = AppTheme.Teal,
)

@Composable
fun FacturareTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = if (isSystemInDarkTheme()) DarkColors else LightColors,
        content = content,
    )
}
