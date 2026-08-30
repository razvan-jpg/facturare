package ro.dateconta.facturare.core.db

import androidx.room.Database
import androidx.room.RoomDatabase

@Database(
    entities = [
        LocalClientEntity::class,
        LocalProductEntity::class,
        LocalDocumentEntity::class,
        LocalSeriesEntity::class,
        LocalPaymentEntity::class,
        OutboxOperationEntity::class,
        SyncMetaEntity::class,
    ],
    version = 1,
    exportSchema = false,
)
abstract class FacturareDatabase : RoomDatabase() {
    abstract fun clientDao(): LocalClientDao
    abstract fun productDao(): LocalProductDao
    abstract fun documentDao(): LocalDocumentDao
    abstract fun seriesDao(): LocalSeriesDao
    abstract fun paymentDao(): LocalPaymentDao
    abstract fun outboxDao(): OutboxDao
    abstract fun syncMetaDao(): SyncMetaDao
}
