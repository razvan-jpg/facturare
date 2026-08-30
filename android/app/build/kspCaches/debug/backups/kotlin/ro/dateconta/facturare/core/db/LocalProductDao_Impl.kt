package ro.dateconta.facturare.core.db

import androidx.room.EntityInsertAdapter
import androidx.room.RoomDatabase
import androidx.room.coroutines.createFlow
import androidx.room.util.getColumnIndexOrThrow
import androidx.room.util.performSuspending
import androidx.sqlite.SQLiteStatement
import javax.`annotation`.processing.Generated
import kotlin.Boolean
import kotlin.Double
import kotlin.Int
import kotlin.Long
import kotlin.String
import kotlin.Suppress
import kotlin.Unit
import kotlin.collections.List
import kotlin.collections.MutableList
import kotlin.collections.mutableListOf
import kotlin.reflect.KClass
import kotlinx.coroutines.flow.Flow

@Generated(value = ["androidx.room.RoomProcessor"])
@Suppress(names = ["UNCHECKED_CAST", "DEPRECATION", "REDUNDANT_PROJECTION", "REMOVAL"])
public class LocalProductDao_Impl(
  __db: RoomDatabase,
) : LocalProductDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfLocalProductEntity: EntityInsertAdapter<LocalProductEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfLocalProductEntity = object : EntityInsertAdapter<LocalProductEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `local_products` (`clientUUID`,`serverId`,`companyId`,`name`,`sku`,`unit`,`type`,`price`,`vatRate`,`productDescription`,`active`,`updatedAt`,`pendingSync`,`isDeleted`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: LocalProductEntity) {
        statement.bindText(1, entity.clientUUID)
        val _tmpServerId: Int? = entity.serverId
        if (_tmpServerId == null) {
          statement.bindNull(2)
        } else {
          statement.bindLong(2, _tmpServerId.toLong())
        }
        statement.bindLong(3, entity.companyId.toLong())
        statement.bindText(4, entity.name)
        val _tmpSku: String? = entity.sku
        if (_tmpSku == null) {
          statement.bindNull(5)
        } else {
          statement.bindText(5, _tmpSku)
        }
        statement.bindText(6, entity.unit)
        statement.bindText(7, entity.type)
        statement.bindDouble(8, entity.price)
        statement.bindDouble(9, entity.vatRate)
        val _tmpProductDescription: String? = entity.productDescription
        if (_tmpProductDescription == null) {
          statement.bindNull(10)
        } else {
          statement.bindText(10, _tmpProductDescription)
        }
        val _tmp: Int = if (entity.active) 1 else 0
        statement.bindLong(11, _tmp.toLong())
        statement.bindLong(12, entity.updatedAt)
        val _tmp_1: Int = if (entity.pendingSync) 1 else 0
        statement.bindLong(13, _tmp_1.toLong())
        val _tmp_2: Int = if (entity.isDeleted) 1 else 0
        statement.bindLong(14, _tmp_2.toLong())
      }
    }
  }

  public override suspend fun upsert(entity: LocalProductEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfLocalProductEntity.insert(_connection, entity)
  }

  public override fun observeAll(): Flow<List<LocalProductEntity>> {
    val _sql: String = "SELECT * FROM local_products WHERE isDeleted = 0 ORDER BY name"
    return createFlow(__db, false, arrayOf("local_products")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfSku: Int = getColumnIndexOrThrow(_stmt, "sku")
        val _columnIndexOfUnit: Int = getColumnIndexOrThrow(_stmt, "unit")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfPrice: Int = getColumnIndexOrThrow(_stmt, "price")
        val _columnIndexOfVatRate: Int = getColumnIndexOrThrow(_stmt, "vatRate")
        val _columnIndexOfProductDescription: Int = getColumnIndexOrThrow(_stmt, "productDescription")
        val _columnIndexOfActive: Int = getColumnIndexOrThrow(_stmt, "active")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: MutableList<LocalProductEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalProductEntity
          val _tmpClientUUID: String
          _tmpClientUUID = _stmt.getText(_columnIndexOfClientUUID)
          val _tmpServerId: Int?
          if (_stmt.isNull(_columnIndexOfServerId)) {
            _tmpServerId = null
          } else {
            _tmpServerId = _stmt.getLong(_columnIndexOfServerId).toInt()
          }
          val _tmpCompanyId: Int
          _tmpCompanyId = _stmt.getLong(_columnIndexOfCompanyId).toInt()
          val _tmpName: String
          _tmpName = _stmt.getText(_columnIndexOfName)
          val _tmpSku: String?
          if (_stmt.isNull(_columnIndexOfSku)) {
            _tmpSku = null
          } else {
            _tmpSku = _stmt.getText(_columnIndexOfSku)
          }
          val _tmpUnit: String
          _tmpUnit = _stmt.getText(_columnIndexOfUnit)
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpPrice: Double
          _tmpPrice = _stmt.getDouble(_columnIndexOfPrice)
          val _tmpVatRate: Double
          _tmpVatRate = _stmt.getDouble(_columnIndexOfVatRate)
          val _tmpProductDescription: String?
          if (_stmt.isNull(_columnIndexOfProductDescription)) {
            _tmpProductDescription = null
          } else {
            _tmpProductDescription = _stmt.getText(_columnIndexOfProductDescription)
          }
          val _tmpActive: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfActive).toInt()
          _tmpActive = _tmp != 0
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _item = LocalProductEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpSku,_tmpUnit,_tmpType,_tmpPrice,_tmpVatRate,_tmpProductDescription,_tmpActive,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getAll(): List<LocalProductEntity> {
    val _sql: String = "SELECT * FROM local_products WHERE isDeleted = 0 ORDER BY name"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfSku: Int = getColumnIndexOrThrow(_stmt, "sku")
        val _columnIndexOfUnit: Int = getColumnIndexOrThrow(_stmt, "unit")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfPrice: Int = getColumnIndexOrThrow(_stmt, "price")
        val _columnIndexOfVatRate: Int = getColumnIndexOrThrow(_stmt, "vatRate")
        val _columnIndexOfProductDescription: Int = getColumnIndexOrThrow(_stmt, "productDescription")
        val _columnIndexOfActive: Int = getColumnIndexOrThrow(_stmt, "active")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: MutableList<LocalProductEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalProductEntity
          val _tmpClientUUID: String
          _tmpClientUUID = _stmt.getText(_columnIndexOfClientUUID)
          val _tmpServerId: Int?
          if (_stmt.isNull(_columnIndexOfServerId)) {
            _tmpServerId = null
          } else {
            _tmpServerId = _stmt.getLong(_columnIndexOfServerId).toInt()
          }
          val _tmpCompanyId: Int
          _tmpCompanyId = _stmt.getLong(_columnIndexOfCompanyId).toInt()
          val _tmpName: String
          _tmpName = _stmt.getText(_columnIndexOfName)
          val _tmpSku: String?
          if (_stmt.isNull(_columnIndexOfSku)) {
            _tmpSku = null
          } else {
            _tmpSku = _stmt.getText(_columnIndexOfSku)
          }
          val _tmpUnit: String
          _tmpUnit = _stmt.getText(_columnIndexOfUnit)
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpPrice: Double
          _tmpPrice = _stmt.getDouble(_columnIndexOfPrice)
          val _tmpVatRate: Double
          _tmpVatRate = _stmt.getDouble(_columnIndexOfVatRate)
          val _tmpProductDescription: String?
          if (_stmt.isNull(_columnIndexOfProductDescription)) {
            _tmpProductDescription = null
          } else {
            _tmpProductDescription = _stmt.getText(_columnIndexOfProductDescription)
          }
          val _tmpActive: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfActive).toInt()
          _tmpActive = _tmp != 0
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _item = LocalProductEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpSku,_tmpUnit,_tmpType,_tmpPrice,_tmpVatRate,_tmpProductDescription,_tmpActive,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByUuid(uuid: String): LocalProductEntity? {
    val _sql: String = "SELECT * FROM local_products WHERE clientUUID = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, uuid)
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfSku: Int = getColumnIndexOrThrow(_stmt, "sku")
        val _columnIndexOfUnit: Int = getColumnIndexOrThrow(_stmt, "unit")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfPrice: Int = getColumnIndexOrThrow(_stmt, "price")
        val _columnIndexOfVatRate: Int = getColumnIndexOrThrow(_stmt, "vatRate")
        val _columnIndexOfProductDescription: Int = getColumnIndexOrThrow(_stmt, "productDescription")
        val _columnIndexOfActive: Int = getColumnIndexOrThrow(_stmt, "active")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: LocalProductEntity?
        if (_stmt.step()) {
          val _tmpClientUUID: String
          _tmpClientUUID = _stmt.getText(_columnIndexOfClientUUID)
          val _tmpServerId: Int?
          if (_stmt.isNull(_columnIndexOfServerId)) {
            _tmpServerId = null
          } else {
            _tmpServerId = _stmt.getLong(_columnIndexOfServerId).toInt()
          }
          val _tmpCompanyId: Int
          _tmpCompanyId = _stmt.getLong(_columnIndexOfCompanyId).toInt()
          val _tmpName: String
          _tmpName = _stmt.getText(_columnIndexOfName)
          val _tmpSku: String?
          if (_stmt.isNull(_columnIndexOfSku)) {
            _tmpSku = null
          } else {
            _tmpSku = _stmt.getText(_columnIndexOfSku)
          }
          val _tmpUnit: String
          _tmpUnit = _stmt.getText(_columnIndexOfUnit)
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpPrice: Double
          _tmpPrice = _stmt.getDouble(_columnIndexOfPrice)
          val _tmpVatRate: Double
          _tmpVatRate = _stmt.getDouble(_columnIndexOfVatRate)
          val _tmpProductDescription: String?
          if (_stmt.isNull(_columnIndexOfProductDescription)) {
            _tmpProductDescription = null
          } else {
            _tmpProductDescription = _stmt.getText(_columnIndexOfProductDescription)
          }
          val _tmpActive: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfActive).toInt()
          _tmpActive = _tmp != 0
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _result = LocalProductEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpSku,_tmpUnit,_tmpType,_tmpPrice,_tmpVatRate,_tmpProductDescription,_tmpActive,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
        } else {
          _result = null
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByServerId(serverId: Int): LocalProductEntity? {
    val _sql: String = "SELECT * FROM local_products WHERE serverId = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindLong(_argIndex, serverId.toLong())
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfSku: Int = getColumnIndexOrThrow(_stmt, "sku")
        val _columnIndexOfUnit: Int = getColumnIndexOrThrow(_stmt, "unit")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfPrice: Int = getColumnIndexOrThrow(_stmt, "price")
        val _columnIndexOfVatRate: Int = getColumnIndexOrThrow(_stmt, "vatRate")
        val _columnIndexOfProductDescription: Int = getColumnIndexOrThrow(_stmt, "productDescription")
        val _columnIndexOfActive: Int = getColumnIndexOrThrow(_stmt, "active")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: LocalProductEntity?
        if (_stmt.step()) {
          val _tmpClientUUID: String
          _tmpClientUUID = _stmt.getText(_columnIndexOfClientUUID)
          val _tmpServerId: Int?
          if (_stmt.isNull(_columnIndexOfServerId)) {
            _tmpServerId = null
          } else {
            _tmpServerId = _stmt.getLong(_columnIndexOfServerId).toInt()
          }
          val _tmpCompanyId: Int
          _tmpCompanyId = _stmt.getLong(_columnIndexOfCompanyId).toInt()
          val _tmpName: String
          _tmpName = _stmt.getText(_columnIndexOfName)
          val _tmpSku: String?
          if (_stmt.isNull(_columnIndexOfSku)) {
            _tmpSku = null
          } else {
            _tmpSku = _stmt.getText(_columnIndexOfSku)
          }
          val _tmpUnit: String
          _tmpUnit = _stmt.getText(_columnIndexOfUnit)
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpPrice: Double
          _tmpPrice = _stmt.getDouble(_columnIndexOfPrice)
          val _tmpVatRate: Double
          _tmpVatRate = _stmt.getDouble(_columnIndexOfVatRate)
          val _tmpProductDescription: String?
          if (_stmt.isNull(_columnIndexOfProductDescription)) {
            _tmpProductDescription = null
          } else {
            _tmpProductDescription = _stmt.getText(_columnIndexOfProductDescription)
          }
          val _tmpActive: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfActive).toInt()
          _tmpActive = _tmp != 0
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _result = LocalProductEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpSku,_tmpUnit,_tmpType,_tmpPrice,_tmpVatRate,_tmpProductDescription,_tmpActive,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
        } else {
          _result = null
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public companion object {
    public fun getRequiredConverters(): List<KClass<*>> = emptyList()
  }
}
