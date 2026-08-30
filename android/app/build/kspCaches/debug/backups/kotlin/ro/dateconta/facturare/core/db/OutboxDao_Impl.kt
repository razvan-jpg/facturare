package ro.dateconta.facturare.core.db

import androidx.room.EntityInsertAdapter
import androidx.room.RoomDatabase
import androidx.room.coroutines.createFlow
import androidx.room.util.getColumnIndexOrThrow
import androidx.room.util.performSuspending
import androidx.sqlite.SQLiteStatement
import javax.`annotation`.processing.Generated
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
public class OutboxDao_Impl(
  __db: RoomDatabase,
) : OutboxDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfOutboxOperationEntity: EntityInsertAdapter<OutboxOperationEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfOutboxOperationEntity = object : EntityInsertAdapter<OutboxOperationEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `outbox_operations` (`opId`,`entity`,`action`,`clientUUID`,`serverId`,`payloadJSON`,`attempts`,`lastError`,`createdAt`) VALUES (?,?,?,?,?,?,?,?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: OutboxOperationEntity) {
        statement.bindText(1, entity.opId)
        statement.bindText(2, entity.entity)
        statement.bindText(3, entity.action)
        val _tmpClientUUID: String? = entity.clientUUID
        if (_tmpClientUUID == null) {
          statement.bindNull(4)
        } else {
          statement.bindText(4, _tmpClientUUID)
        }
        val _tmpServerId: Int? = entity.serverId
        if (_tmpServerId == null) {
          statement.bindNull(5)
        } else {
          statement.bindLong(5, _tmpServerId.toLong())
        }
        statement.bindText(6, entity.payloadJSON)
        statement.bindLong(7, entity.attempts.toLong())
        val _tmpLastError: String? = entity.lastError
        if (_tmpLastError == null) {
          statement.bindNull(8)
        } else {
          statement.bindText(8, _tmpLastError)
        }
        statement.bindLong(9, entity.createdAt)
      }
    }
  }

  public override suspend fun insert(entity: OutboxOperationEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfOutboxOperationEntity.insert(_connection, entity)
  }

  public override suspend fun getAll(): List<OutboxOperationEntity> {
    val _sql: String = "SELECT * FROM outbox_operations ORDER BY createdAt"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfOpId: Int = getColumnIndexOrThrow(_stmt, "opId")
        val _columnIndexOfEntity: Int = getColumnIndexOrThrow(_stmt, "entity")
        val _columnIndexOfAction: Int = getColumnIndexOrThrow(_stmt, "action")
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfPayloadJSON: Int = getColumnIndexOrThrow(_stmt, "payloadJSON")
        val _columnIndexOfAttempts: Int = getColumnIndexOrThrow(_stmt, "attempts")
        val _columnIndexOfLastError: Int = getColumnIndexOrThrow(_stmt, "lastError")
        val _columnIndexOfCreatedAt: Int = getColumnIndexOrThrow(_stmt, "createdAt")
        val _result: MutableList<OutboxOperationEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: OutboxOperationEntity
          val _tmpOpId: String
          _tmpOpId = _stmt.getText(_columnIndexOfOpId)
          val _tmpEntity: String
          _tmpEntity = _stmt.getText(_columnIndexOfEntity)
          val _tmpAction: String
          _tmpAction = _stmt.getText(_columnIndexOfAction)
          val _tmpClientUUID: String?
          if (_stmt.isNull(_columnIndexOfClientUUID)) {
            _tmpClientUUID = null
          } else {
            _tmpClientUUID = _stmt.getText(_columnIndexOfClientUUID)
          }
          val _tmpServerId: Int?
          if (_stmt.isNull(_columnIndexOfServerId)) {
            _tmpServerId = null
          } else {
            _tmpServerId = _stmt.getLong(_columnIndexOfServerId).toInt()
          }
          val _tmpPayloadJSON: String
          _tmpPayloadJSON = _stmt.getText(_columnIndexOfPayloadJSON)
          val _tmpAttempts: Int
          _tmpAttempts = _stmt.getLong(_columnIndexOfAttempts).toInt()
          val _tmpLastError: String?
          if (_stmt.isNull(_columnIndexOfLastError)) {
            _tmpLastError = null
          } else {
            _tmpLastError = _stmt.getText(_columnIndexOfLastError)
          }
          val _tmpCreatedAt: Long
          _tmpCreatedAt = _stmt.getLong(_columnIndexOfCreatedAt)
          _item = OutboxOperationEntity(_tmpOpId,_tmpEntity,_tmpAction,_tmpClientUUID,_tmpServerId,_tmpPayloadJSON,_tmpAttempts,_tmpLastError,_tmpCreatedAt)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override fun observeCount(): Flow<Int> {
    val _sql: String = "SELECT COUNT(*) FROM outbox_operations"
    return createFlow(__db, false, arrayOf("outbox_operations")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _result: Int
        if (_stmt.step()) {
          val _tmp: Int
          _tmp = _stmt.getLong(0).toInt()
          _result = _tmp
        } else {
          _result = 0
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun delete(opId: String) {
    val _sql: String = "DELETE FROM outbox_operations WHERE opId = ?"
    return performSuspending(__db, false, true) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, opId)
        _stmt.step()
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun markFailed(opId: String, error: String?) {
    val _sql: String = "UPDATE outbox_operations SET attempts = attempts + 1, lastError = ? WHERE opId = ?"
    return performSuspending(__db, false, true) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        if (error == null) {
          _stmt.bindNull(_argIndex)
        } else {
          _stmt.bindText(_argIndex, error)
        }
        _argIndex = 2
        _stmt.bindText(_argIndex, opId)
        _stmt.step()
      } finally {
        _stmt.close()
      }
    }
  }

  public companion object {
    public fun getRequiredConverters(): List<KClass<*>> = emptyList()
  }
}
