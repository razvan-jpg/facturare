package ro.dateconta.facturare.core.db

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update
import kotlinx.coroutines.flow.Flow

@Dao
interface LocalClientDao {
    @Query("SELECT * FROM local_clients WHERE isDeleted = 0 ORDER BY name")
    fun observeAll(): Flow<List<LocalClientEntity>>

    @Query("SELECT * FROM local_clients WHERE isDeleted = 0 ORDER BY name")
    suspend fun getAll(): List<LocalClientEntity>

    @Query("SELECT * FROM local_clients WHERE clientUUID = :uuid LIMIT 1")
    suspend fun getByUuid(uuid: String): LocalClientEntity?

    @Query("SELECT * FROM local_clients WHERE serverId = :serverId LIMIT 1")
    suspend fun getByServerId(serverId: Int): LocalClientEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(entity: LocalClientEntity)

    @Update
    suspend fun update(entity: LocalClientEntity)
}

@Dao
interface LocalProductDao {
    @Query("SELECT * FROM local_products WHERE isDeleted = 0 ORDER BY name")
    fun observeAll(): Flow<List<LocalProductEntity>>

    @Query("SELECT * FROM local_products WHERE isDeleted = 0 ORDER BY name")
    suspend fun getAll(): List<LocalProductEntity>

    @Query("SELECT * FROM local_products WHERE clientUUID = :uuid LIMIT 1")
    suspend fun getByUuid(uuid: String): LocalProductEntity?

    @Query("SELECT * FROM local_products WHERE serverId = :serverId LIMIT 1")
    suspend fun getByServerId(serverId: Int): LocalProductEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(entity: LocalProductEntity)
}

@Dao
interface LocalDocumentDao {
    @Query("SELECT * FROM local_documents WHERE isDeleted = 0 ORDER BY updatedAt DESC")
    fun observeAll(): Flow<List<LocalDocumentEntity>>

    @Query("SELECT * FROM local_documents WHERE isDeleted = 0 AND type = :type ORDER BY updatedAt DESC")
    fun observeByType(type: String): Flow<List<LocalDocumentEntity>>

    @Query("SELECT * FROM local_documents WHERE clientUUID = :uuid LIMIT 1")
    suspend fun getByUuid(uuid: String): LocalDocumentEntity?

    @Query("SELECT * FROM local_documents WHERE serverId = :serverId LIMIT 1")
    suspend fun getByServerId(serverId: Int): LocalDocumentEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(entity: LocalDocumentEntity)
}

@Dao
interface LocalSeriesDao {
    @Query("SELECT * FROM local_series ORDER BY type, prefix")
    fun observeAll(): Flow<List<LocalSeriesEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(entity: LocalSeriesEntity)
}

@Dao
interface LocalPaymentDao {
    @Query("SELECT * FROM local_payments ORDER BY updatedAt DESC")
    fun observeAll(): Flow<List<LocalPaymentEntity>>

    @Query("SELECT * FROM local_payments WHERE clientUUID = :uuid LIMIT 1")
    suspend fun getByUuid(uuid: String): LocalPaymentEntity?

    @Query("SELECT * FROM local_payments WHERE serverId = :serverId LIMIT 1")
    suspend fun getByServerId(serverId: Int): LocalPaymentEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(entity: LocalPaymentEntity)
}

@Dao
interface OutboxDao {
    @Query("SELECT * FROM outbox_operations ORDER BY createdAt")
    suspend fun getAll(): List<OutboxOperationEntity>

    @Query("SELECT COUNT(*) FROM outbox_operations")
    fun observeCount(): Flow<Int>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(entity: OutboxOperationEntity)

    @Query("DELETE FROM outbox_operations WHERE opId = :opId")
    suspend fun delete(opId: String)

    @Query("UPDATE outbox_operations SET attempts = attempts + 1, lastError = :error WHERE opId = :opId")
    suspend fun markFailed(opId: String, error: String?)
}

@Dao
interface SyncMetaDao {
    @Query("SELECT value FROM sync_meta WHERE `key` = :key LIMIT 1")
    suspend fun get(key: String): String?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun set(entity: SyncMetaEntity)
}
