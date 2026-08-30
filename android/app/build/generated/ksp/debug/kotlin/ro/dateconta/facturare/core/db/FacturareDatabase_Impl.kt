package ro.dateconta.facturare.core.db

import androidx.room.InvalidationTracker
import androidx.room.RoomOpenDelegate
import androidx.room.migration.AutoMigrationSpec
import androidx.room.migration.Migration
import androidx.room.util.TableInfo
import androidx.room.util.TableInfo.Companion.read
import androidx.room.util.dropFtsSyncTriggers
import androidx.sqlite.SQLiteConnection
import androidx.sqlite.execSQL
import javax.`annotation`.processing.Generated
import kotlin.Lazy
import kotlin.String
import kotlin.Suppress
import kotlin.collections.List
import kotlin.collections.Map
import kotlin.collections.MutableList
import kotlin.collections.MutableMap
import kotlin.collections.MutableSet
import kotlin.collections.Set
import kotlin.collections.mutableListOf
import kotlin.collections.mutableMapOf
import kotlin.collections.mutableSetOf
import kotlin.reflect.KClass

@Generated(value = ["androidx.room.RoomProcessor"])
@Suppress(names = ["UNCHECKED_CAST", "DEPRECATION", "REDUNDANT_PROJECTION", "REMOVAL"])
public class FacturareDatabase_Impl : FacturareDatabase() {
  private val _localClientDao: Lazy<LocalClientDao> = lazy {
    LocalClientDao_Impl(this)
  }

  private val _localProductDao: Lazy<LocalProductDao> = lazy {
    LocalProductDao_Impl(this)
  }

  private val _localDocumentDao: Lazy<LocalDocumentDao> = lazy {
    LocalDocumentDao_Impl(this)
  }

  private val _localSeriesDao: Lazy<LocalSeriesDao> = lazy {
    LocalSeriesDao_Impl(this)
  }

  private val _localPaymentDao: Lazy<LocalPaymentDao> = lazy {
    LocalPaymentDao_Impl(this)
  }

  private val _outboxDao: Lazy<OutboxDao> = lazy {
    OutboxDao_Impl(this)
  }

  private val _syncMetaDao: Lazy<SyncMetaDao> = lazy {
    SyncMetaDao_Impl(this)
  }

  protected override fun createOpenDelegate(): RoomOpenDelegate {
    val _openDelegate: RoomOpenDelegate = object : RoomOpenDelegate(1, "4a4b8460acd81e6f2aede2cb90093147", "bac8b659c793faa1eb58526fc2c36218") {
      public override fun createAllTables(connection: SQLiteConnection) {
        connection.execSQL("CREATE TABLE IF NOT EXISTS `local_clients` (`clientUUID` TEXT NOT NULL, `serverId` INTEGER, `companyId` INTEGER NOT NULL, `name` TEXT NOT NULL, `type` TEXT NOT NULL, `cui` TEXT, `regCom` TEXT, `cnp` TEXT, `address` TEXT, `city` TEXT, `county` TEXT, `country` TEXT, `phone` TEXT, `email` TEXT, `iban` TEXT, `bankName` TEXT, `notes` TEXT, `openingBalance` REAL NOT NULL, `openingBalanceDate` TEXT, `updatedAt` INTEGER NOT NULL, `pendingSync` INTEGER NOT NULL, `isDeleted` INTEGER NOT NULL, PRIMARY KEY(`clientUUID`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS `local_products` (`clientUUID` TEXT NOT NULL, `serverId` INTEGER, `companyId` INTEGER NOT NULL, `name` TEXT NOT NULL, `sku` TEXT, `unit` TEXT NOT NULL, `type` TEXT NOT NULL, `price` REAL NOT NULL, `vatRate` REAL NOT NULL, `productDescription` TEXT, `active` INTEGER NOT NULL, `updatedAt` INTEGER NOT NULL, `pendingSync` INTEGER NOT NULL, `isDeleted` INTEGER NOT NULL, PRIMARY KEY(`clientUUID`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS `local_documents` (`clientUUID` TEXT NOT NULL, `serverId` INTEGER, `companyId` INTEGER NOT NULL, `clientServerId` INTEGER, `type` TEXT NOT NULL, `status` TEXT NOT NULL, `series` TEXT, `number` INTEGER, `numberFull` TEXT, `issueDate` TEXT NOT NULL, `dueDate` TEXT, `currency` TEXT NOT NULL, `subtotal` REAL NOT NULL, `vatTotal` REAL NOT NULL, `total` REAL NOT NULL, `paidAmount` REAL NOT NULL, `paymentStatus` TEXT NOT NULL, `notes` TEXT, `clientName` TEXT, `clientCui` TEXT, `clientEmail` TEXT, `efacturaStatus` TEXT, `efacturaError` TEXT, `itemsJSON` TEXT NOT NULL, `updatedAt` INTEGER NOT NULL, `pendingSync` INTEGER NOT NULL, `pendingIssue` INTEGER NOT NULL, `isDeleted` INTEGER NOT NULL, PRIMARY KEY(`clientUUID`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS `local_series` (`serverId` INTEGER NOT NULL, `companyId` INTEGER NOT NULL, `type` TEXT NOT NULL, `prefix` TEXT NOT NULL, `firstNumber` INTEGER NOT NULL, `nextNumber` INTEGER NOT NULL, `year` INTEGER NOT NULL, `active` INTEGER NOT NULL, `isDefault` INTEGER NOT NULL, `updatedAt` INTEGER NOT NULL, PRIMARY KEY(`serverId`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS `local_payments` (`clientUUID` TEXT NOT NULL, `serverId` INTEGER, `companyId` INTEGER NOT NULL, `documentServerId` INTEGER, `amount` REAL NOT NULL, `method` TEXT NOT NULL, `paidAt` TEXT NOT NULL, `notes` TEXT, `updatedAt` INTEGER NOT NULL, `pendingSync` INTEGER NOT NULL, PRIMARY KEY(`clientUUID`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS `outbox_operations` (`opId` TEXT NOT NULL, `entity` TEXT NOT NULL, `action` TEXT NOT NULL, `clientUUID` TEXT, `serverId` INTEGER, `payloadJSON` TEXT NOT NULL, `attempts` INTEGER NOT NULL, `lastError` TEXT, `createdAt` INTEGER NOT NULL, PRIMARY KEY(`opId`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS `sync_meta` (`key` TEXT NOT NULL, `value` TEXT NOT NULL, PRIMARY KEY(`key`))")
        connection.execSQL("CREATE TABLE IF NOT EXISTS room_master_table (id INTEGER PRIMARY KEY,identity_hash TEXT)")
        connection.execSQL("INSERT OR REPLACE INTO room_master_table (id,identity_hash) VALUES(42, '4a4b8460acd81e6f2aede2cb90093147')")
      }

      public override fun dropAllTables(connection: SQLiteConnection) {
        connection.execSQL("DROP TABLE IF EXISTS `local_clients`")
        connection.execSQL("DROP TABLE IF EXISTS `local_products`")
        connection.execSQL("DROP TABLE IF EXISTS `local_documents`")
        connection.execSQL("DROP TABLE IF EXISTS `local_series`")
        connection.execSQL("DROP TABLE IF EXISTS `local_payments`")
        connection.execSQL("DROP TABLE IF EXISTS `outbox_operations`")
        connection.execSQL("DROP TABLE IF EXISTS `sync_meta`")
      }

      public override fun onCreate(connection: SQLiteConnection) {
      }

      public override fun onOpen(connection: SQLiteConnection) {
        internalInitInvalidationTracker(connection)
      }

      public override fun onPreMigrate(connection: SQLiteConnection) {
        dropFtsSyncTriggers(connection)
      }

      public override fun onPostMigrate(connection: SQLiteConnection) {
      }

      public override fun onValidateSchema(connection: SQLiteConnection): RoomOpenDelegate.ValidationResult {
        val _columnsLocalClients: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsLocalClients.put("clientUUID", TableInfo.Column("clientUUID", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("serverId", TableInfo.Column("serverId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("companyId", TableInfo.Column("companyId", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("name", TableInfo.Column("name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("type", TableInfo.Column("type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("cui", TableInfo.Column("cui", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("regCom", TableInfo.Column("regCom", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("cnp", TableInfo.Column("cnp", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("address", TableInfo.Column("address", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("city", TableInfo.Column("city", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("county", TableInfo.Column("county", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("country", TableInfo.Column("country", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("phone", TableInfo.Column("phone", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("email", TableInfo.Column("email", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("iban", TableInfo.Column("iban", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("bankName", TableInfo.Column("bankName", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("notes", TableInfo.Column("notes", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("openingBalance", TableInfo.Column("openingBalance", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("openingBalanceDate", TableInfo.Column("openingBalanceDate", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("updatedAt", TableInfo.Column("updatedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("pendingSync", TableInfo.Column("pendingSync", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalClients.put("isDeleted", TableInfo.Column("isDeleted", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysLocalClients: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesLocalClients: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoLocalClients: TableInfo = TableInfo("local_clients", _columnsLocalClients, _foreignKeysLocalClients, _indicesLocalClients)
        val _existingLocalClients: TableInfo = read(connection, "local_clients")
        if (!_infoLocalClients.equals(_existingLocalClients)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |local_clients(ro.dateconta.facturare.core.db.LocalClientEntity).
              | Expected:
              |""".trimMargin() + _infoLocalClients + """
              |
              | Found:
              |""".trimMargin() + _existingLocalClients)
        }
        val _columnsLocalProducts: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsLocalProducts.put("clientUUID", TableInfo.Column("clientUUID", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("serverId", TableInfo.Column("serverId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("companyId", TableInfo.Column("companyId", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("name", TableInfo.Column("name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("sku", TableInfo.Column("sku", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("unit", TableInfo.Column("unit", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("type", TableInfo.Column("type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("price", TableInfo.Column("price", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("vatRate", TableInfo.Column("vatRate", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("productDescription", TableInfo.Column("productDescription", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("active", TableInfo.Column("active", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("updatedAt", TableInfo.Column("updatedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("pendingSync", TableInfo.Column("pendingSync", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalProducts.put("isDeleted", TableInfo.Column("isDeleted", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysLocalProducts: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesLocalProducts: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoLocalProducts: TableInfo = TableInfo("local_products", _columnsLocalProducts, _foreignKeysLocalProducts, _indicesLocalProducts)
        val _existingLocalProducts: TableInfo = read(connection, "local_products")
        if (!_infoLocalProducts.equals(_existingLocalProducts)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |local_products(ro.dateconta.facturare.core.db.LocalProductEntity).
              | Expected:
              |""".trimMargin() + _infoLocalProducts + """
              |
              | Found:
              |""".trimMargin() + _existingLocalProducts)
        }
        val _columnsLocalDocuments: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsLocalDocuments.put("clientUUID", TableInfo.Column("clientUUID", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("serverId", TableInfo.Column("serverId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("companyId", TableInfo.Column("companyId", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("clientServerId", TableInfo.Column("clientServerId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("type", TableInfo.Column("type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("status", TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("series", TableInfo.Column("series", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("number", TableInfo.Column("number", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("numberFull", TableInfo.Column("numberFull", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("issueDate", TableInfo.Column("issueDate", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("dueDate", TableInfo.Column("dueDate", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("currency", TableInfo.Column("currency", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("subtotal", TableInfo.Column("subtotal", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("vatTotal", TableInfo.Column("vatTotal", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("total", TableInfo.Column("total", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("paidAmount", TableInfo.Column("paidAmount", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("paymentStatus", TableInfo.Column("paymentStatus", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("notes", TableInfo.Column("notes", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("clientName", TableInfo.Column("clientName", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("clientCui", TableInfo.Column("clientCui", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("clientEmail", TableInfo.Column("clientEmail", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("efacturaStatus", TableInfo.Column("efacturaStatus", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("efacturaError", TableInfo.Column("efacturaError", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("itemsJSON", TableInfo.Column("itemsJSON", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("updatedAt", TableInfo.Column("updatedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("pendingSync", TableInfo.Column("pendingSync", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("pendingIssue", TableInfo.Column("pendingIssue", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalDocuments.put("isDeleted", TableInfo.Column("isDeleted", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysLocalDocuments: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesLocalDocuments: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoLocalDocuments: TableInfo = TableInfo("local_documents", _columnsLocalDocuments, _foreignKeysLocalDocuments, _indicesLocalDocuments)
        val _existingLocalDocuments: TableInfo = read(connection, "local_documents")
        if (!_infoLocalDocuments.equals(_existingLocalDocuments)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |local_documents(ro.dateconta.facturare.core.db.LocalDocumentEntity).
              | Expected:
              |""".trimMargin() + _infoLocalDocuments + """
              |
              | Found:
              |""".trimMargin() + _existingLocalDocuments)
        }
        val _columnsLocalSeries: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsLocalSeries.put("serverId", TableInfo.Column("serverId", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("companyId", TableInfo.Column("companyId", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("type", TableInfo.Column("type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("prefix", TableInfo.Column("prefix", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("firstNumber", TableInfo.Column("firstNumber", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("nextNumber", TableInfo.Column("nextNumber", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("year", TableInfo.Column("year", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("active", TableInfo.Column("active", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("isDefault", TableInfo.Column("isDefault", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalSeries.put("updatedAt", TableInfo.Column("updatedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysLocalSeries: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesLocalSeries: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoLocalSeries: TableInfo = TableInfo("local_series", _columnsLocalSeries, _foreignKeysLocalSeries, _indicesLocalSeries)
        val _existingLocalSeries: TableInfo = read(connection, "local_series")
        if (!_infoLocalSeries.equals(_existingLocalSeries)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |local_series(ro.dateconta.facturare.core.db.LocalSeriesEntity).
              | Expected:
              |""".trimMargin() + _infoLocalSeries + """
              |
              | Found:
              |""".trimMargin() + _existingLocalSeries)
        }
        val _columnsLocalPayments: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsLocalPayments.put("clientUUID", TableInfo.Column("clientUUID", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("serverId", TableInfo.Column("serverId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("companyId", TableInfo.Column("companyId", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("documentServerId", TableInfo.Column("documentServerId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("amount", TableInfo.Column("amount", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("method", TableInfo.Column("method", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("paidAt", TableInfo.Column("paidAt", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("notes", TableInfo.Column("notes", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("updatedAt", TableInfo.Column("updatedAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsLocalPayments.put("pendingSync", TableInfo.Column("pendingSync", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysLocalPayments: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesLocalPayments: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoLocalPayments: TableInfo = TableInfo("local_payments", _columnsLocalPayments, _foreignKeysLocalPayments, _indicesLocalPayments)
        val _existingLocalPayments: TableInfo = read(connection, "local_payments")
        if (!_infoLocalPayments.equals(_existingLocalPayments)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |local_payments(ro.dateconta.facturare.core.db.LocalPaymentEntity).
              | Expected:
              |""".trimMargin() + _infoLocalPayments + """
              |
              | Found:
              |""".trimMargin() + _existingLocalPayments)
        }
        val _columnsOutboxOperations: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsOutboxOperations.put("opId", TableInfo.Column("opId", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("entity", TableInfo.Column("entity", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("action", TableInfo.Column("action", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("clientUUID", TableInfo.Column("clientUUID", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("serverId", TableInfo.Column("serverId", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("payloadJSON", TableInfo.Column("payloadJSON", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("attempts", TableInfo.Column("attempts", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("lastError", TableInfo.Column("lastError", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsOutboxOperations.put("createdAt", TableInfo.Column("createdAt", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysOutboxOperations: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesOutboxOperations: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoOutboxOperations: TableInfo = TableInfo("outbox_operations", _columnsOutboxOperations, _foreignKeysOutboxOperations, _indicesOutboxOperations)
        val _existingOutboxOperations: TableInfo = read(connection, "outbox_operations")
        if (!_infoOutboxOperations.equals(_existingOutboxOperations)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |outbox_operations(ro.dateconta.facturare.core.db.OutboxOperationEntity).
              | Expected:
              |""".trimMargin() + _infoOutboxOperations + """
              |
              | Found:
              |""".trimMargin() + _existingOutboxOperations)
        }
        val _columnsSyncMeta: MutableMap<String, TableInfo.Column> = mutableMapOf()
        _columnsSyncMeta.put("key", TableInfo.Column("key", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY))
        _columnsSyncMeta.put("value", TableInfo.Column("value", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY))
        val _foreignKeysSyncMeta: MutableSet<TableInfo.ForeignKey> = mutableSetOf()
        val _indicesSyncMeta: MutableSet<TableInfo.Index> = mutableSetOf()
        val _infoSyncMeta: TableInfo = TableInfo("sync_meta", _columnsSyncMeta, _foreignKeysSyncMeta, _indicesSyncMeta)
        val _existingSyncMeta: TableInfo = read(connection, "sync_meta")
        if (!_infoSyncMeta.equals(_existingSyncMeta)) {
          return RoomOpenDelegate.ValidationResult(false, """
              |sync_meta(ro.dateconta.facturare.core.db.SyncMetaEntity).
              | Expected:
              |""".trimMargin() + _infoSyncMeta + """
              |
              | Found:
              |""".trimMargin() + _existingSyncMeta)
        }
        return RoomOpenDelegate.ValidationResult(true, null)
      }
    }
    return _openDelegate
  }

  protected override fun createInvalidationTracker(): InvalidationTracker {
    val _shadowTablesMap: MutableMap<String, String> = mutableMapOf()
    val _viewTables: MutableMap<String, Set<String>> = mutableMapOf()
    return InvalidationTracker(this, _shadowTablesMap, _viewTables, "local_clients", "local_products", "local_documents", "local_series", "local_payments", "outbox_operations", "sync_meta")
  }

  public override fun clearAllTables() {
    super.performClear(false, "local_clients", "local_products", "local_documents", "local_series", "local_payments", "outbox_operations", "sync_meta")
  }

  protected override fun getRequiredTypeConverterClasses(): Map<KClass<*>, List<KClass<*>>> {
    val _typeConvertersMap: MutableMap<KClass<*>, List<KClass<*>>> = mutableMapOf()
    _typeConvertersMap.put(LocalClientDao::class, LocalClientDao_Impl.getRequiredConverters())
    _typeConvertersMap.put(LocalProductDao::class, LocalProductDao_Impl.getRequiredConverters())
    _typeConvertersMap.put(LocalDocumentDao::class, LocalDocumentDao_Impl.getRequiredConverters())
    _typeConvertersMap.put(LocalSeriesDao::class, LocalSeriesDao_Impl.getRequiredConverters())
    _typeConvertersMap.put(LocalPaymentDao::class, LocalPaymentDao_Impl.getRequiredConverters())
    _typeConvertersMap.put(OutboxDao::class, OutboxDao_Impl.getRequiredConverters())
    _typeConvertersMap.put(SyncMetaDao::class, SyncMetaDao_Impl.getRequiredConverters())
    return _typeConvertersMap
  }

  public override fun getRequiredAutoMigrationSpecClasses(): Set<KClass<out AutoMigrationSpec>> {
    val _autoMigrationSpecsSet: MutableSet<KClass<out AutoMigrationSpec>> = mutableSetOf()
    return _autoMigrationSpecsSet
  }

  public override fun createAutoMigrations(autoMigrationSpecs: Map<KClass<out AutoMigrationSpec>, AutoMigrationSpec>): List<Migration> {
    val _autoMigrations: MutableList<Migration> = mutableListOf()
    return _autoMigrations
  }

  public override fun clientDao(): LocalClientDao = _localClientDao.value

  public override fun productDao(): LocalProductDao = _localProductDao.value

  public override fun documentDao(): LocalDocumentDao = _localDocumentDao.value

  public override fun seriesDao(): LocalSeriesDao = _localSeriesDao.value

  public override fun paymentDao(): LocalPaymentDao = _localPaymentDao.value

  public override fun outboxDao(): OutboxDao = _outboxDao.value

  public override fun syncMetaDao(): SyncMetaDao = _syncMetaDao.value
}
