package ro.dateconta.facturare.core.util

import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone

object DateFormats {
    private val isoFormats = listOf(
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSSXXX", Locale.US),
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US),
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US),
        SimpleDateFormat("yyyy-MM-dd", Locale.US),
    ).onEach { it.timeZone = TimeZone.getTimeZone("UTC") }

    fun parseIso(raw: String?): Long {
        if (raw.isNullOrBlank()) return System.currentTimeMillis()
        for (fmt in isoFormats) {
            try {
                return fmt.parse(raw)?.time ?: continue
            } catch (_: Exception) {
            }
        }
        return System.currentTimeMillis()
    }

    fun today(): String {
        val fmt = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        return fmt.format(Date())
    }

    fun formatMoney(amount: Double): String {
        return String.format(Locale("ro", "RO"), "%,.2f RON", amount)
    }
}
