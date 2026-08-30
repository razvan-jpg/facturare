package ro.dateconta.facturare.core.subscription

import ro.dateconta.facturare.core.api.ApiClient
import ro.dateconta.facturare.core.auth.AuthRepository
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter

object SubscriptionConfig {
    const val MONTHLY_PRODUCT_ID = "ro.dateconta.facturare.premium.monthly"
    const val THREE_MONTHS_PRODUCT_ID = "ro.dateconta.facturare.premium.3months"
    const val SIX_MONTHS_PRODUCT_ID = "ro.dateconta.facturare.premium.6months"
    const val YEARLY_PRODUCT_ID = "ro.dateconta.facturare.premium.yearly"

    val allProductIds = listOf(
        MONTHLY_PRODUCT_ID,
        THREE_MONTHS_PRODUCT_ID,
        SIX_MONTHS_PRODUCT_ID,
        YEARLY_PRODUCT_ID,
    )

    private val bucharest = ZoneId.of("Europe/Bucharest")
    private val freeUntil = LocalDateTime.of(2027, 3, 31, 23, 59, 59)

    val isInFreePeriod: Boolean
        get() = LocalDateTime.now(bucharest).isBefore(freeUntil) ||
            LocalDateTime.now(bucharest).isEqual(freeUntil)

    fun periodLabel(productId: String): String = when (productId) {
        MONTHLY_PRODUCT_ID -> "1 lună"
        THREE_MONTHS_PRODUCT_ID -> "3 luni"
        SIX_MONTHS_PRODUCT_ID -> "6 luni"
        YEARLY_PRODUCT_ID -> "1 an"
        else -> productId
    }

    fun sortKey(productId: String): Int = when (productId) {
        MONTHLY_PRODUCT_ID -> 0
        THREE_MONTHS_PRODUCT_ID -> 1
        SIX_MONTHS_PRODUCT_ID -> 2
        YEARLY_PRODUCT_ID -> 3
        else -> 99
    }
}

/**
 * Android folosește accesul web (AccessGate) — nu iOS IAP.
 * Perioada gratuită locală + hasAccess din profilul utilizatorului.
 * Google Play Billing poate fi adăugat ulterior (structură pregătită).
 */
class SubscriptionRepository(
    private val auth: AuthRepository,
) {
    var isLoading: Boolean = false
        private set
    var errorMessage: String? = null
        private set
    var lastSyncedAt: Long? = null
        private set

    val hasAccess: Boolean
        get() {
            if (auth.isAdmin) return true
            auth.user?.hasAccess?.let { return it }
            return SubscriptionConfig.isInFreePeriod
        }

    val isInFreePeriod: Boolean
        get() = SubscriptionConfig.isInFreePeriod

    val accessLabel: String?
        get() = auth.user?.accessLabel

    suspend fun refresh() {
        isLoading = true
        errorMessage = null
        try {
            auth.refreshMe()
            lastSyncedAt = System.currentTimeMillis()
        } catch (e: Exception) {
            errorMessage = e.message
        } finally {
            isLoading = false
        }
    }

    fun formatAccessUntil(): String? {
        if (!SubscriptionConfig.isInFreePeriod) return null
        val formatter = DateTimeFormatter.ofPattern("d MMMM yyyy")
        return LocalDate.of(2027, 3, 31).format(formatter)
    }
}
