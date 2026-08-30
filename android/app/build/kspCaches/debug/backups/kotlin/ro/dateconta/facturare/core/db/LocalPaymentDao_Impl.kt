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
public class LocalPaymentDao_Impl(
  __db: RoomDatabase,
) : LocalPaymentDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfLocalPaymentEntity: EntityInsertAdapter<LocalPaymentEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfLocalPaymentEntity = object : EntityInsertAdapter<LocalPaymentEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `local_payments` (`clientUUID`,`serverId`,`companyId`,`documentServerId`,`amount`,`method`,`paidAt`,`notes`,`updatedAt`,`pendingSync`) VALUES (?,?,?,?,?,?,?,?,?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: LocalPaymentEntity) {
        statement.bindText(1, entity.clientUUID)
        val _tmpServerId: Int? = entity.serverId
        if (_tmpServerId == null) {
          statement.bindNull(2)
        } else {
          statement.bindLong(2, _tmpServerId.toLong())
        }
        statement.bindLong(3, entity.companyId.toLong())
        val _tmpDocumentServerId: Int? = entity.documentServerId
        if (_tmpDocumentServerId == null) {
          statement.bindNull(4)
        } else {
          statement.bindLong(4, _tmpDocumentServerId.toLong())
        }
        statement.bindDouble(5, entity.amount)
        statement.bindText(6, entity.method)
        statement.bindText(7, entity.paidAt)
        val _tmpNotes: String? = entity.notes
        if (_tmpNotes == null) {
          statement.bindNull(8)
        } else {
          statement.bindText(8, _tmpNotes)
        }
        statement.bindLong(9, entity.updatedAt)
        val _tmp: Int = if (entity.pendingSync) 1 else 0
        statement.bindLong(10, _tmp.toLong())
      }
    }
  }

  public override suspend fun upsert(entity: LocalPaymentEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfLocalPaymentEntity.insert(_connection, entity)
  }

  public override fun observeAll(): Flow<List<LocalPaymentEntity>> {
    val _sql: String = "SELECT * FROM local_payments ORDER BY updatedAt DESC"
    return createFlow(__db, false, arrayOf("local_payments")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfDocumentServerId: Int = getColumnIndexOrThrow(_stmt, "documentServerId")
        val _columnIndexOfAmount: Int = getColumnIndexOrThrow(_stmt, "amount")
        val _columnIndexOfMethod: Int = getColumnIndexOrThrow(_stmt, "method")
        val _columnIndexOfPaidAt: Int = getColumnIndexOrThrow(_stmt, "paidAt")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _result: MutableList<LocalPaymentEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalPaymentEntity
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
          val _tmpDocumentServerId: Int?
          if (_stmt.isNull(_columnIndexOfDocumentServerId)) {
            _tmpDocumentServerId = null
          } else {
            _tmpDocumentServerId = _stmt.getLong(_columnIndexOfDocumentServerId).toInt()
          }
          val _tmpAmount: Double
          _tmpAmount = _stmt.getDouble(_columnIndexOfAmount)
          val _tmpMethod: String
          _tmpMethod = _stmt.getText(_columnIndexOfMethod)
          val _tmpPaidAt: String
          _tmpPaidAt = _stmt.getText(_columnIndexOfPaidAt)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          _item = LocalPaymentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpDocumentServerId,_tmpAmount,_tmpMethod,_tmpPaidAt,_tmpNotes,_tmpUpdatedAt,_tmpPendingSync)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByUuid(uuid: String): LocalPaymentEntity? {
    val _sql: String = "SELECT * FROM local_payments WHERE clientUUID = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, uuid)
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfDocumentServerId: Int = getColumnIndexOrThrow(_stmt, "documentServerId")
        val _columnIndexOfAmount: Int = getColumnIndexOrThrow(_stmt, "amount")
        val _columnIndexOfMethod: Int = getColumnIndexOrThrow(_stmt, "method")
        val _columnIndexOfPaidAt: Int = getColumnIndexOrThrow(_stmt, "paidAt")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _result: LocalPaymentEntity?
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
          val _tmpDocumentServerId: Int?
          if (_stmt.isNull(_columnIndexOfDocumentServerId)) {
            _tmpDocumentServerId = null
          } else {
            _tmpDocumentServerId = _stmt.getLong(_columnIndexOfDocumentServerId).toInt()
          }
          val _tmpAmount: Double
          _tmpAmount = _stmt.getDouble(_columnIndexOfAmount)
          val _tmpMethod: String
          _tmpMethod = _stmt.getText(_columnIndexOfMethod)
          val _tmpPaidAt: String
          _tmpPaidAt = _stmt.getText(_columnIndexOfPaidAt)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          _result = LocalPaymentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpDocumentServerId,_tmpAmount,_tmpMethod,_tmpPaidAt,_tmpNotes,_tmpUpdatedAt,_tmpPendingSync)
        } else {
          _result = null
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByServerId(serverId: Int): LocalPaymentEntity? {
    val _sql: String = "SELECT * FROM local_payments WHERE serverId = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindLong(_argIndex, serverId.toLong())
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfDocumentServerId: Int = getColumnIndexOrThrow(_stmt, "documentServerId")
        val _columnIndexOfAmount: Int = getColumnIndexOrThrow(_stmt, "amount")
        val _columnIndexOfMethod: Int = getColumnIndexOrThrow(_stmt, "method")
        val _columnIndexOfPaidAt: Int = getColumnIndexOrThrow(_stmt, "paidAt")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _result: LocalPaymentEntity?
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
          val _tmpDocumentServerId: Int?
          if (_stmt.isNull(_columnIndexOfDocumentServerId)) {
            _tmpDocumentServerId = null
          } else {
            _tmpDocumentServerId = _stmt.getLong(_columnIndexOfDocumentServerId).toInt()
          }
          val _tmpAmount: Double
          _tmpAmount = _stmt.getDouble(_columnIndexOfAmount)
          val _tmpMethod: String
          _tmpMethod = _stmt.getText(_columnIndexOfMethod)
          val _tmpPaidAt: String
          _tmpPaidAt = _stmt.getText(_columnIndexOfPaidAt)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          _result = LocalPaymentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpDocumentServerId,_tmpAmount,_tmpMethod,_tmpPaidAt,_tmpNotes,_tmpUpdatedAt,_tmpPendingSync)
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
