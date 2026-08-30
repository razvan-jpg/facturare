package ro.dateconta.facturare.core.db

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "local_clients")
data class LocalClientEntity(
    @PrimaryKey val clientUUID: String,
    val serverId: Int? = null,
    val companyId: Int,
    val name: String,
    val type: String = "company",
    val cui: String? = null,
    val regCom: String? = null,
    val cnp: String? = null,
    val address: String? = null,
    val city: String? = null,
    val county: String? = null,
    val country: String? = null,
    val phone: String? = null,
    val email: String? = null,
    val iban: String? = null,
    val bankName: String? = null,
    val notes: String? = null,
    val openingBalance: Double = 0.0,
    val openingBalanceDate: String? = null,
    val updatedAt: Long = System.currentTimeMillis(),
    val pendingSync: Boolean = false,
    val isDeleted: Boolean = false,
)

@Entity(tableName = "local_products")
data class LocalProductEntity(
    @PrimaryKey val clientUUID: String,
    val serverId: Int? = null,
    val companyId: Int,
    val name: String,
    val sku: String? = null,
    val unit: String = "buc",
    val type: String = "service",
    val price: Double = 0.0,
    val vatRate: Double = 21.0,
    val productDescription: String? = null,
    val active: Boolean = true,
    val updatedAt: Long = System.currentTimeMillis(),
    val pendingSync: Boolean = false,
    val isDeleted: Boolean = false,
)

@Entity(tableName = "local_documents")
data class LocalDocumentEntity(
    @PrimaryKey val clientUUID: String,
    val serverId: Int? = null,
    val companyId: Int,
    val clientServerId: Int? = null,
    val type: String = "invoice",
    val status: String = "draft",
    val series: String? = null,
    val number: Int? = null,
    val numberFull: String? = null,
    val issueDate: String,
    val dueDate: String? = null,
    val currency: String = "RON",
    val subtotal: Double = 0.0,
    val vatTotal: Double = 0.0,
    val total: Double = 0.0,
    val paidAmount: Double = 0.0,
    val paymentStatus: String = "unpaid",
    val notes: String? = null,
    val clientName: String? = null,
    val clientCui: String? = null,
    val clientEmail: String? = null,
    val efacturaStatus: String? = null,
    val efacturaError: String? = null,
    val itemsJSON: String = "[]",
    val updatedAt: Long = System.currentTimeMillis(),
    val pendingSync: Boolean = false,
    val pendingIssue: Boolean = false,
    val isDeleted: Boolean = false,
)

@Entity(tableName = "local_series")
data class LocalSeriesEntity(
    @PrimaryKey val serverId: Int,
    val companyId: Int,
    val type: String,
    val prefix: String,
    val firstNumber: Int = 1,
    val nextNumber: Int = 1,
    val year: Int,
    val active: Boolean = true,
    val isDefault: Boolean = false,
    val updatedAt: Long = System.currentTimeMillis(),
)

@Entity(tableName = "local_payments")
data class LocalPaymentEntity(
    @PrimaryKey val clientUUID: String,
    val serverId: Int? = null,
    val companyId: Int,
    val documentServerId: Int? = null,
    val amount: Double = 0.0,
    val method: String = "op",
    val paidAt: String,
    val notes: String? = null,
    val updatedAt: Long = System.currentTimeMillis(),
    val pendingSync: Boolean = false,
)

@Entity(tableName = "outbox_operations")
data class OutboxOperationEntity(
    @PrimaryKey val opId: String,
    val entity: String,
    val action: String,
    val clientUUID: String? = null,
    val serverId: Int? = null,
    val payloadJSON: String = "{}",
    val attempts: Int = 0,
    val lastError: String? = null,
    val createdAt: Long = System.currentTimeMillis(),
)

@Entity(tableName = "sync_meta")
data class SyncMetaEntity(
    @PrimaryKey val key: String,
    val value: String,
)
