package ro.dateconta.facturare.core.sync

import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import ro.dateconta.facturare.core.api.ApiClient
import ro.dateconta.facturare.core.api.ApiException
import ro.dateconta.facturare.core.api.SyncPushOperation
import ro.dateconta.facturare.core.api.SyncPushRequest
import ro.dateconta.facturare.core.api.SyncPushResponse
import ro.dateconta.facturare.core.api.SyncPullResponse
import ro.dateconta.facturare.core.db.FacturareDatabase
import ro.dateconta.facturare.core.db.LocalClientEntity
import ro.dateconta.facturare.core.db.LocalDocumentEntity
import ro.dateconta.facturare.core.db.LocalPaymentEntity
import ro.dateconta.facturare.core.db.LocalProductEntity
import ro.dateconta.facturare.core.db.LocalSeriesEntity
import ro.dateconta.facturare.core.db.OutboxOperationEntity
import ro.dateconta.facturare.core.db.SyncMetaEntity
import ro.dateconta.facturare.core.util.DateFormats
import java.util.UUID

sealed class SyncStatus(val label: String) {
    data object Idle : SyncStatus("Sincronizat")
    data object Syncing : SyncStatus("Sincronizare…")
    data object Offline : SyncStatus("Offline")
    data class Error(val message: String) : SyncStatus(message)
    data object Ok : SyncStatus("Sincronizat")
}

class SyncEngine(
    private val api: ApiClient,
    private val db: FacturareDatabase,
) {
    private val mutex = Mutex()
    private val json = Json { ignoreUnknownKeys = true }

    var status: SyncStatus = SyncStatus.Idle
        private set
    var lastSyncedAt: Long? = null
        private set
    var pendingCount: Int = 0
        private set

    val hasPendingSync: Boolean
        get() = pendingCount > 0 || status is SyncStatus.Syncing

    suspend fun refreshPendingCount() {
        pendingCount = db.outboxDao().getAll().size
    }

    suspend fun enqueue(
        entity: String,
        action: String,
        clientUUID: String? = null,
        serverId: Int? = null,
        payload: Map<String, Any?> = emptyMap(),
    ) {
        val sanitized = sanitizePayload(payload)
        val payloadJson = json.encodeToString(
            JsonObject(sanitized.mapValues { (_, v) -> toJsonElement(v) }),
        )
        db.outboxDao().insert(
            OutboxOperationEntity(
                opId = UUID.randomUUID().toString(),
                entity = entity,
                action = action,
                clientUUID = clientUUID,
                serverId = serverId,
                payloadJSON = payloadJson,
            ),
        )
        refreshPendingCount()
        syncNow()
    }

    suspend fun syncNow() {
        mutex.withLock {
            performSync()
        }
    }

    private suspend fun performSync() {
        status = SyncStatus.Syncing
        try {
            pushOutbox()
            val serverTime = pull()
            status = SyncStatus.Ok
            lastSyncedAt = System.currentTimeMillis()
            api.companyId?.let { companyId ->
                serverTime?.let {
                    db.syncMetaDao().set(SyncMetaEntity(syncCursorKey(companyId), it))
                }
            }
            refreshPendingCount()
        } catch (e: ApiException.Offline) {
            status = SyncStatus.Offline
        } catch (e: ApiException.Unauthorized) {
            status = SyncStatus.Error("Sesiune expirată")
        } catch (e: Exception) {
            status = SyncStatus.Error(e.message ?: "Eroare sync")
        }
    }

    private suspend fun pushOutbox() {
        val ops = db.outboxDao().getAll()
        if (ops.isEmpty()) return

        val operations = ops.map { op ->
            val payload = json.parseToJsonElement(op.payloadJSON).jsonObject
            SyncPushOperation(
                opId = op.opId,
                entity = op.entity,
                action = op.action,
                clientUuid = op.clientUUID,
                serverId = op.serverId,
                payload = payload,
            )
        }

        val bodyJson = json.encodeToString(SyncPushRequest(operations))
        val response: SyncPushResponse = api.requestJson("POST", "sync/push", bodyJson)

        for (result in response.results) {
            val op = ops.firstOrNull { it.opId == result.opId } ?: continue
            if (result.ok == true) {
                applyPushResult(result)
                db.outboxDao().delete(op.opId)
            } else {
                db.outboxDao().markFailed(op.opId, result.error)
            }
        }
    }

    private suspend fun applyPushResult(result: ro.dateconta.facturare.core.api.SyncOpResult) {
        val uuid = result.clientUuid ?: return
        val serverId = result.serverId ?: return
        when (result.entity) {
            "client" -> db.clientDao().getByUuid(uuid)?.let {
                db.clientDao().upsert(it.copy(serverId = serverId, pendingSync = false))
            }
            "product" -> db.productDao().getByUuid(uuid)?.let {
                db.productDao().upsert(it.copy(serverId = serverId, pendingSync = false))
            }
            "document" -> db.documentDao().getByUuid(uuid)?.let {
                db.documentDao().upsert(it.copy(serverId = serverId, pendingSync = false, pendingIssue = false))
            }
            "payment" -> db.paymentDao().getByUuid(uuid)?.let {
                db.paymentDao().upsert(it.copy(serverId = serverId, pendingSync = false))
            }
        }
    }

    private suspend fun pull(): String? {
        val activeCompanyId = api.companyId ?: return null
        val since = db.syncMetaDao().get(syncCursorKey(activeCompanyId))
        var afterDocumentId = 0
        var afterPaymentId = 0
        var needDocuments = true
        var needPayments = true
        var serverTime: String? = null
        var page = 0

        while (needDocuments || needPayments) {
            page += 1
            val query = buildMap {
                since?.let { put("since", it) }
                if (afterDocumentId > 0 && needDocuments) put("after_document_id", afterDocumentId.toString())
                if (afterPaymentId > 0 && needPayments) put("after_payment_id", afterPaymentId.toString())
            }

            val response: SyncPullResponse = api.request("GET", "sync", query = query)
            val companyId = response.company?.id ?: activeCompanyId
            serverTime = response.serverTime

            if (page == 1) {
                response.clients.forEach { upsertClient(it, companyId) }
                response.products.forEach { upsertProduct(it, companyId) }
                response.series.forEach { upsertSeries(it, companyId) }
            }

            if (needDocuments) {
                response.documents.forEach {
                    upsertDocument(it, companyId)
                    afterDocumentId = maxOf(afterDocumentId, it.id)
                }
                needDocuments = response.hasMoreDocuments ?: (response.documents.size >= 500)
            }
            if (needPayments) {
                response.payments.forEach {
                    upsertPayment(it, companyId)
                    afterPaymentId = maxOf(afterPaymentId, it.id)
                }
                needPayments = response.hasMorePayments ?: (response.payments.size >= 500)
            }

            if (page >= 40) break
        }

        return serverTime
    }

    private suspend fun upsertClient(c: ro.dateconta.facturare.core.api.ApiClientDto, companyId: Int) {
        val existing = db.clientDao().getByServerId(c.id)
        if (existing?.pendingSync == true) return
        val entity = (existing ?: LocalClientEntity(
            clientUUID = UUID.randomUUID().toString(),
            companyId = c.companyId ?: companyId,
            name = c.name,
        )).copy(
            serverId = c.id,
            companyId = c.companyId ?: companyId,
            name = c.name,
            type = c.type ?: "company",
            cui = c.cui,
            regCom = c.regCom,
            cnp = c.cnp,
            address = c.address,
            city = c.city,
            county = c.county,
            country = c.country,
            phone = c.phone,
            email = c.email,
            iban = c.iban,
            bankName = c.bankName,
            notes = c.notes,
            openingBalance = c.openingBalance ?: 0.0,
            openingBalanceDate = c.openingBalanceDate,
            updatedAt = DateFormats.parseIso(c.updatedAt),
            isDeleted = false,
            pendingSync = false,
        )
        db.clientDao().upsert(entity)
    }

    private suspend fun upsertProduct(p: ro.dateconta.facturare.core.api.ApiProduct, companyId: Int) {
        val existing = db.productDao().getByServerId(p.id)
        if (existing?.pendingSync == true) return
        val entity = (existing ?: LocalProductEntity(
            clientUUID = UUID.randomUUID().toString(),
            companyId = p.companyId ?: companyId,
            name = p.name,
        )).copy(
            serverId = p.id,
            companyId = p.companyId ?: companyId,
            name = p.name,
            sku = p.sku,
            unit = p.unit ?: "buc",
            type = p.type ?: "service",
            price = p.price,
            vatRate = p.vatRate,
            productDescription = p.description,
            active = p.active ?: true,
            updatedAt = DateFormats.parseIso(p.updatedAt),
            isDeleted = false,
            pendingSync = false,
        )
        db.productDao().upsert(entity)
    }

    private suspend fun upsertDocument(d: ro.dateconta.facturare.core.api.ApiDocument, companyId: Int) {
        val existing = db.documentDao().getByServerId(d.id)
        if (existing?.pendingSync == true || existing?.pendingIssue == true) return
        val itemsJson = json.encodeToString(d.items ?: emptyList<ro.dateconta.facturare.core.api.ApiDocumentItem>())
        val entity = (existing ?: LocalDocumentEntity(
            clientUUID = UUID.randomUUID().toString(),
            companyId = d.companyId ?: companyId,
            issueDate = d.issueDate ?: DateFormats.today(),
        )).copy(
            serverId = d.id,
            companyId = d.companyId ?: companyId,
            clientServerId = d.clientId,
            type = d.type,
            status = d.status,
            series = d.series,
            number = d.number,
            numberFull = d.numberFull,
            issueDate = d.issueDate ?: DateFormats.today(),
            dueDate = d.dueDate,
            currency = d.currency ?: "RON",
            subtotal = d.subtotal ?: 0.0,
            vatTotal = d.vatTotal ?: 0.0,
            total = d.total ?: 0.0,
            paidAmount = d.paidAmount ?: 0.0,
            paymentStatus = d.paymentStatus ?: "unpaid",
            notes = d.notes,
            clientName = d.clientName,
            clientCui = d.clientCui,
            clientEmail = d.clientEmail,
            efacturaStatus = d.efacturaStatus,
            efacturaError = d.efacturaError,
            itemsJSON = itemsJson,
            updatedAt = DateFormats.parseIso(d.updatedAt),
            isDeleted = false,
            pendingSync = false,
            pendingIssue = false,
        )
        db.documentDao().upsert(entity)
    }

    private suspend fun upsertSeries(s: ro.dateconta.facturare.core.api.ApiSeries, companyId: Int) {
        val year = s.year ?: java.util.Calendar.getInstance().get(java.util.Calendar.YEAR)
        db.seriesDao().upsert(
            LocalSeriesEntity(
                serverId = s.id,
                companyId = companyId,
                type = s.type,
                prefix = s.prefix,
                firstNumber = s.firstNumber ?: s.nextNumber ?: 1,
                nextNumber = s.nextNumber ?: 1,
                year = year,
                active = s.active ?: true,
                isDefault = s.isDefault ?: false,
                updatedAt = System.currentTimeMillis(),
            ),
        )
    }

    private suspend fun upsertPayment(p: ro.dateconta.facturare.core.api.ApiPayment, companyId: Int) {
        val existing = db.paymentDao().getByServerId(p.id)
        if (existing?.pendingSync == true) return
        val entity = (existing ?: LocalPaymentEntity(
            clientUUID = UUID.randomUUID().toString(),
            companyId = companyId,
            paidAt = p.paidAt ?: DateFormats.today(),
            amount = p.amount,
        )).copy(
            serverId = p.id,
            companyId = p.companyId ?: companyId,
            documentServerId = p.documentId,
            amount = p.amount,
            method = p.method ?: "op",
            paidAt = p.paidAt ?: DateFormats.today(),
            notes = p.notes,
            updatedAt = DateFormats.parseIso(p.updatedAt),
            pendingSync = false,
        )
        db.paymentDao().upsert(entity)
    }

    private fun syncCursorKey(companyId: Int) = "last_sync_company_$companyId"

    private fun sanitizePayload(map: Map<String, Any?>): Map<String, Any?> {
        return map.mapNotNull { (k, v) ->
            when (v) {
                null -> null
                is Map<*, *> -> {
                    @Suppress("UNCHECKED_CAST")
                    k to sanitizePayload(v as Map<String, Any?>)
                }
                is List<*> -> k to v
                else -> k to v
            }
        }.toMap()
    }

    private fun toJsonElement(value: Any?): JsonElement = when (value) {
        null -> JsonPrimitive(null as String?)
        is String -> JsonPrimitive(value)
        is Boolean -> JsonPrimitive(value)
        is Int -> JsonPrimitive(value)
        is Long -> JsonPrimitive(value)
        is Double -> JsonPrimitive(value)
        is Float -> JsonPrimitive(value.toDouble())
        is Map<*, *> -> {
            @Suppress("UNCHECKED_CAST")
            JsonObject((value as Map<String, Any?>).mapValues { (_, v) -> toJsonElement(v) })
        }
        is List<*> -> buildJsonObject { } // fallback - lists encoded separately
        else -> JsonPrimitive(value.toString())
    }
}
