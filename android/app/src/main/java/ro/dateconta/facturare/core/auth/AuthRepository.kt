package ro.dateconta.facturare.core.auth

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import ro.dateconta.facturare.core.api.ApiClient
import ro.dateconta.facturare.core.api.ApiCompany
import ro.dateconta.facturare.core.api.ApiException
import ro.dateconta.facturare.core.api.ApiUser
import ro.dateconta.facturare.core.api.AuthResponse
import ro.dateconta.facturare.core.api.DataEnvelope
import ro.dateconta.facturare.core.api.MeResponse
import ro.dateconta.facturare.core.api.ApiConfig

class TokenStore(context: Context) {
    private val prefs = EncryptedSharedPreferences.create(
        context,
        "facturare_secure_prefs",
        MasterKey.Builder(context).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build(),
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
    )

    fun getToken(): String? = prefs.getString(KEY_TOKEN, null)

    fun setToken(token: String?) {
        prefs.edit().putString(KEY_TOKEN, token).apply()
    }

    fun getCompanyId(): Int? = prefs.getString(KEY_COMPANY, null)?.toIntOrNull()

    fun setCompanyId(id: Int?) {
        prefs.edit().putString(KEY_COMPANY, id?.toString()).apply()
    }

    fun getUserJson(): String? = prefs.getString(KEY_USER, null)

    fun setUserJson(json: String?) {
        prefs.edit().putString(KEY_USER, json).apply()
    }

    fun getCompaniesJson(): String? = prefs.getString(KEY_COMPANIES, null)

    fun setCompaniesJson(json: String?) {
        prefs.edit().putString(KEY_COMPANIES, json).apply()
    }

    fun getWebVersion(): String? = prefs.getString(KEY_WEB_VERSION, null)

    fun setWebVersion(version: String?) {
        prefs.edit().putString(KEY_WEB_VERSION, version).apply()
    }

    fun clear() {
        prefs.edit().clear().apply()
    }

    companion object {
        private const val KEY_TOKEN = "auth_token"
        private const val KEY_COMPANY = "current_company_id"
        private const val KEY_USER = "cached_user_json"
        private const val KEY_COMPANIES = "cached_companies_json"
        private const val KEY_WEB_VERSION = "cached_web_app_version"
    }
}

class AuthRepository(
    private val api: ApiClient,
    private val tokenStore: TokenStore,
) {
    private val json = Json { ignoreUnknownKeys = true }

    var user: ApiUser? = null
        private set
    var companies: List<ApiCompany> = emptyList()
        private set
    var currentCompanyId: Int? = null
        private set
    var webAppVersion: String? = null
        private set
    var isLoading: Boolean = false
        private set
    var errorMessage: String? = null
        private set

    val isAuthenticated: Boolean get() = tokenStore.getToken() != null && user != null
    val isAdmin: Boolean get() = user?.isAdmin == true
    val currentCompany: ApiCompany?
        get() = companies.firstOrNull { it.id == currentCompanyId } ?: companies.firstOrNull()

    init {
        restoreFromCache()
    }

    private fun restoreFromCache() {
        val token = tokenStore.getToken()
        currentCompanyId = tokenStore.getCompanyId()
        webAppVersion = tokenStore.getWebVersion()
        tokenStore.getUserJson()?.let {
            user = runCatching { json.decodeFromString<ApiUser>(it) }.getOrNull()
        }
        tokenStore.getCompaniesJson()?.let {
            companies = runCatching { json.decodeFromString<List<ApiCompany>>(it) }.getOrDefault(emptyList())
        }
        api.setCredentials(token, currentCompanyId)
    }

    fun can(ability: String): Boolean = currentCompany?.can(ability) == true

    suspend fun login(email: String, password: String): Boolean {
        isLoading = true
        errorMessage = null
        return try {
            val response: AuthResponse = api.request(
                method = "POST",
                path = "login",
                body = mapOf(
                    "email" to email,
                    "password" to password,
                    "device_name" to ApiConfig.DEVICE_NAME,
                ),
                authorized = false,
            )
            applyAuth(response)
            true
        } catch (e: Exception) {
            errorMessage = e.message
            false
        } finally {
            isLoading = false
        }
    }

    suspend fun register(
        name: String,
        email: String,
        password: String,
        passwordConfirmation: String,
    ): Boolean {
        isLoading = true
        errorMessage = null
        return try {
            val response: AuthResponse = api.request(
                method = "POST",
                path = "register",
                body = mapOf(
                    "name" to name,
                    "email" to email,
                    "password" to password,
                    "password_confirmation" to passwordConfirmation,
                    "device_name" to ApiConfig.DEVICE_NAME,
                ),
                authorized = false,
            )
            applyAuth(response)
            true
        } catch (e: Exception) {
            errorMessage = e.message
            false
        } finally {
            isLoading = false
        }
    }

    suspend fun refreshMe() {
        if (tokenStore.getToken() == null) return
        try {
            val response: MeResponse = api.request("GET", "me")
            user = response.user
            companies = sanitizeCompanies(response.companies, response.user)
            response.appVersion?.takeIf { it.isNotBlank() }?.let {
                webAppVersion = it
                tokenStore.setWebVersion(it)
            }
            persistUserCache()
            val serverCompanyId = response.user.currentCompanyId
            val resolved = when {
                serverCompanyId != null && companies.any { it.id == serverCompanyId } -> serverCompanyId
                else -> companies.firstOrNull()?.id
            }
            if (currentCompanyId != resolved) {
                currentCompanyId = resolved
                tokenStore.setCompanyId(resolved)
            }
            api.setCredentials(tokenStore.getToken(), currentCompanyId)
        } catch (e: ApiException.Unauthorized) {
            logoutLocal()
        }
    }

    suspend fun switchCompany(company: ApiCompany): Boolean {
        if (company.id == currentCompanyId) return true
        val previous = currentCompanyId
        api.companyId = company.id
        return try {
            api.request<DataEnvelope<ApiCompany>>(
                method = "POST",
                path = "companies/${company.id}/switch",
            )
            currentCompanyId = company.id
            tokenStore.setCompanyId(company.id)
            true
        } catch (e: Exception) {
            api.companyId = previous
            errorMessage = e.message
            false
        }
    }

    suspend fun logout() {
        if (tokenStore.getToken() != null) {
            runCatching { api.rawRequest("POST", "logout") }
        }
        logoutLocal()
    }

    suspend fun deleteAccount(password: String): Boolean {
        errorMessage = null
        return try {
            api.request<Map<String, Boolean>>(
                method = "DELETE",
                path = "profile",
                body = mapOf("password" to password, "confirm" to true),
            )
            logoutLocal()
            true
        } catch (e: Exception) {
            errorMessage = e.message
            false
        }
    }

    fun logoutLocal() {
        tokenStore.clear()
        user = null
        companies = emptyList()
        currentCompanyId = null
        api.setCredentials(null, null)
    }

    private fun applyAuth(response: AuthResponse) {
        val token = response.token ?: return
        tokenStore.setToken(token)
        user = response.user
        companies = sanitizeCompanies(response.companies, response.user)
        currentCompanyId = response.user.currentCompanyId ?: companies.firstOrNull()?.id
        response.appVersion?.let {
            webAppVersion = it
            tokenStore.setWebVersion(it)
        }
        persistUserCache()
        tokenStore.setCompanyId(currentCompanyId)
        api.setCredentials(token, currentCompanyId)
    }

    private fun persistUserCache() {
        user?.let { tokenStore.setUserJson(json.encodeToString(it)) }
        tokenStore.setCompaniesJson(json.encodeToString(companies))
    }

    private fun sanitizeCompanies(companies: List<ApiCompany>, user: ApiUser): List<ApiCompany> {
        if (user.email.equals("demo@dateconta.ro", ignoreCase = true)) {
            return companies.filterNot { it.cui == "38254880" }
        }
        return companies
    }
}
