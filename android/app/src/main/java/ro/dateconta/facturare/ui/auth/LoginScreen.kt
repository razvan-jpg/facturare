package ro.dateconta.facturare.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.ui.theme.AppTheme

@Composable
fun LoginScreen(appState: AppState) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var showRegister by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    if (showRegister) {
        RegisterScreen(
            appState = appState,
            onDismiss = { showRegister = false },
        )
        return
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(
                Brush.linearGradient(
                    listOf(AppTheme.Deep, AppTheme.Teal, AppTheme.Accent.copy(alpha = 0.9f)),
                ),
            )
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Text("DateConta", fontSize = 36.sp, fontWeight = FontWeight.Bold, color = Color.White)
        Text("Facturare", fontSize = 20.sp, fontWeight = FontWeight.SemiBold, color = Color.White.copy(0.9f))
        Text(
            "Sincronizat cu factura.dateconta.ro",
            color = Color.White.copy(0.72f),
            modifier = Modifier.padding(top = 8.dp, bottom = 28.dp),
        )

        Column(
            modifier = Modifier
                .fillMaxWidth()
                .background(Color.White, RoundedCornerShape(16.dp))
                .padding(20.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Text("Autentificare", fontWeight = FontWeight.SemiBold, color = AppTheme.Deep)

            OutlinedTextField(
                value = email,
                onValueChange = { email = it },
                label = { Text("Email") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
            )
            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("Parolă") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                visualTransformation = PasswordVisualTransformation(),
            )

            appState.auth.errorMessage?.let {
                Text(it, color = Color.Red, fontSize = 13.sp)
            }

            Button(
                onClick = {
                    scope.launch { appState.login(email.trim(), password) }
                },
                enabled = !appState.auth.isLoading && email.isNotBlank() && password.isNotBlank(),
                modifier = Modifier.fillMaxWidth(),
                colors = ButtonDefaults.buttonColors(containerColor = AppTheme.Warm),
            ) {
                if (appState.auth.isLoading) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.height(20.dp))
                } else {
                    Text("Intră în cont")
                }
            }

            TextButton(onClick = { showRegister = true }, modifier = Modifier.align(Alignment.CenterHorizontally)) {
                Text("Cont nou", color = AppTheme.Accent)
            }

            Text(
                "Demo: demo@dateconta.ro / demo1234",
                fontSize = 12.sp,
                color = Color.Gray,
                modifier = Modifier.padding(top = 4.dp),
            )
        }
    }
}

@Composable
fun RegisterScreen(appState: AppState, onDismiss: () -> Unit) {
    var name by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirm by remember { mutableStateOf("") }
    val scope = rememberCoroutineScope()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(AppTheme.Mist)
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Text("Cont nou", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = AppTheme.Deep)
        OutlinedTextField(value = name, onValueChange = { name = it }, label = { Text("Nume") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Email") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = password, onValueChange = { password = it }, label = { Text("Parolă") }, modifier = Modifier.fillMaxWidth(), visualTransformation = PasswordVisualTransformation())
        OutlinedTextField(value = confirm, onValueChange = { confirm = it }, label = { Text("Confirmă parola") }, modifier = Modifier.fillMaxWidth(), visualTransformation = PasswordVisualTransformation())

        appState.auth.errorMessage?.let { Text(it, color = Color.Red) }

        Button(
            onClick = {
                scope.launch {
                    if (appState.register(name.trim(), email.trim(), password, confirm)) {
                        onDismiss()
                    }
                }
            },
            enabled = !appState.auth.isLoading,
            modifier = Modifier.fillMaxWidth(),
        ) { Text("Creează cont") }

        TextButton(onClick = onDismiss) { Text("Înapoi la autentificare") }
        Spacer(Modifier.height(24.dp))
    }
}
