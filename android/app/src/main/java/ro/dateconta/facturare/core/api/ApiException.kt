package ro.dateconta.facturare.core.api

sealed class ApiException(message: String) : Exception(message) {
    class Unauthorized : ApiException("Sesiune expirată. Autentifică-te din nou.")
    class Offline : ApiException("Fără conexiune la internet.")
    class Http(val code: Int, message: String) : ApiException(message)
    class Decoding(message: String) : ApiException("Răspuns invalid de la server: $message")
}
