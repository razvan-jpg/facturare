package ro.dateconta.facturare.core.api

import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.buildJsonArray
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.IOException
import java.net.URLEncoder
import java.util.concurrent.TimeUnit

class ApiClient {
    val json = Json {
        ignoreUnknownKeys = true
        isLenient = true
        encodeDefaults = true
        explicitNulls = false
    }

    private val client = OkHttpClient.Builder()
        .connectTimeout(60, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .build()

    private val jsonMediaType = "application/json".toMediaType()

    @Volatile
    var token: String? = null

    @Volatile
    var companyId: Int? = null

    fun setCredentials(token: String?, companyId: Int?) {
        this.token = token
        this.companyId = companyId
    }

    inline fun <reified T> requestJson(
        method: String,
        path: String,
        jsonBody: String,
        query: Map<String, String> = emptyMap(),
        authorized: Boolean = true,
    ): T {
        val data = rawRequestJson(method, path, jsonBody, query, authorized)
        return try {
            json.decodeFromString(data)
        } catch (e: Exception) {
            throw ApiException.Decoding(e.message ?: "unknown")
        }
    }

    fun rawRequestJson(
        method: String,
        path: String,
        jsonBody: String,
        query: Map<String, String> = emptyMap(),
        authorized: Boolean = true,
    ): String {
        val trimmed = path.trim('/')
        val urlBuilder = StringBuilder(ApiConfig.BASE_URL.trimEnd('/')).append('/').append(trimmed)
        if (query.isNotEmpty()) {
            urlBuilder.append('?')
            urlBuilder.append(
                query.entries.joinToString("&") { (k, v) ->
                    val encoded = URLEncoder.encode(v, Charsets.UTF_8.name())
                        .replace("+", "%2B")
                    "${URLEncoder.encode(k, Charsets.UTF_8.name())}=$encoded"
                },
            )
        }

        val requestBuilder = Request.Builder()
            .url(urlBuilder.toString())
            .header("Accept", "application/json")
            .header("Content-Type", "application/json")
            .header("X-Client", ApiConfig.DEVICE_NAME)
            .header("X-Device-Name", ApiConfig.DEVICE_NAME)

        if (authorized) {
            val t = token ?: throw ApiException.Unauthorized()
            requestBuilder.header("Authorization", "Bearer $t")
            companyId?.let { requestBuilder.header("X-Company-Id", it.toString()) }
        }

        requestBuilder.method(method, jsonBody.toRequestBody(jsonMediaType))

        val response = try {
            client.newCall(requestBuilder.build()).execute()
        } catch (_: IOException) {
            throw ApiException.Offline()
        }

        response.use { resp ->
            val responseBody = resp.body?.string().orEmpty()
            if (resp.code == 401) throw ApiException.Unauthorized()
            if (!resp.isSuccessful) {
                val message = try {
                    json.decodeFromString<ApiErrorBody>(responseBody).message
                } catch (_: Exception) {
                    responseBody.ifBlank { "Eroare ${resp.code}" }
                } ?: "Eroare ${resp.code}"
                throw ApiException.Http(resp.code, message)
            }
            return responseBody
        }
    }

    inline fun <reified T> request(
        method: String,
        path: String,
        body: Map<String, Any?>? = null,
        query: Map<String, String> = emptyMap(),
        authorized: Boolean = true,
    ): T {
        val data = rawRequest(method, path, body, query, authorized)
        return try {
            json.decodeFromString(data)
        } catch (e: Exception) {
            throw ApiException.Decoding(e.message ?: "unknown")
        }
    }

    fun rawRequest(
        method: String,
        path: String,
        body: Map<String, Any?>? = null,
        query: Map<String, String> = emptyMap(),
        authorized: Boolean = true,
    ): String {
        val trimmed = path.trim('/')
        val urlBuilder = StringBuilder(ApiConfig.BASE_URL.trimEnd('/')).append('/').append(trimmed)
        if (query.isNotEmpty()) {
            urlBuilder.append('?')
            urlBuilder.append(
                query.entries.joinToString("&") { (k, v) ->
                    val encoded = URLEncoder.encode(v, Charsets.UTF_8.name())
                        .replace("+", "%2B")
                    "${URLEncoder.encode(k, Charsets.UTF_8.name())}=$encoded"
                },
            )
        }

        val requestBuilder = Request.Builder()
            .url(urlBuilder.toString())
            .header("Accept", "application/json")
            .header("Content-Type", "application/json")
            .header("X-Client", ApiConfig.DEVICE_NAME)
            .header("X-Device-Name", ApiConfig.DEVICE_NAME)

        if (authorized) {
            val t = token ?: throw ApiException.Unauthorized()
            requestBuilder.header("Authorization", "Bearer $t")
            companyId?.let { requestBuilder.header("X-Company-Id", it.toString()) }
        }

        if (body != null) {
            val jsonBody = mapToJsonObject(body)
            requestBuilder.method(method, json.encodeToString(jsonBody).toRequestBody(jsonMediaType))
        } else {
            requestBuilder.method(method, null)
        }

        val response = try {
            client.newCall(requestBuilder.build()).execute()
        } catch (_: IOException) {
            throw ApiException.Offline()
        }

        response.use { resp ->
            val responseBody = resp.body?.string().orEmpty()
            if (resp.code == 401) throw ApiException.Unauthorized()
            if (!resp.isSuccessful) {
                val message = try {
                    json.decodeFromString<ApiErrorBody>(responseBody).message
                } catch (_: Exception) {
                    responseBody.ifBlank { "Eroare ${resp.code}" }
                } ?: "Eroare ${resp.code}"
                throw ApiException.Http(resp.code, message)
            }
            return responseBody
        }
    }

    fun downloadPdf(documentId: Int): ByteArray {
        val request = Request.Builder()
            .url("${ApiConfig.BASE_URL.trimEnd('/')}/documents/$documentId/pdf")
            .header("Accept", "application/pdf")
            .header("X-Client", ApiConfig.DEVICE_NAME)
            .header("X-Device-Name", ApiConfig.DEVICE_NAME)
            .apply {
                val t = token ?: throw ApiException.Unauthorized()
                header("Authorization", "Bearer $t")
                companyId?.let { header("X-Company-Id", it.toString()) }
            }
            .get()
            .build()

        return try {
            client.newCall(request).execute().use { resp ->
                if (resp.code == 401) throw ApiException.Unauthorized()
                if (!resp.isSuccessful) {
                    throw ApiException.Http(resp.code, "Nu pot descărca PDF-ul.")
                }
                resp.body?.bytes() ?: ByteArray(0)
            }
        } catch (_: IOException) {
            throw ApiException.Offline()
        }
    }

    private fun mapToJsonObject(map: Map<String, Any?>): JsonObject {
        return buildJsonObject {
            map.forEach { (key, value) ->
                when (value) {
                    null -> Unit
                    is String -> put(key, value)
                    is Boolean -> put(key, value)
                    is Int -> put(key, value)
                    is Long -> put(key, value)
                    is Double -> put(key, value)
                    is Float -> put(key, value.toDouble())
                    is Map<*, *> -> {
                        @Suppress("UNCHECKED_CAST")
                        put(key, mapToJsonObject(value as Map<String, Any?>))
                    }
                    is List<*> -> put(key, buildJsonArray {
                        value.forEach { item ->
                            when (item) {
                                is Map<*, *> -> {
                                    @Suppress("UNCHECKED_CAST")
                                    add(mapToJsonObject(item as Map<String, Any?>))
                                }
                                is String -> add(JsonPrimitive(item))
                                is Number -> add(JsonPrimitive(item))
                                is Boolean -> add(JsonPrimitive(item))
                                null -> Unit
                                else -> add(JsonPrimitive(item.toString()))
                            }
                        }
                    })
                    else -> put(key, value.toString())
                }
            }
        }
    }
}
