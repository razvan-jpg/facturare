package ro.dateconta.facturare.core.db

import androidx.room.EntityInsertAdapter
import androidx.room.RoomDatabase
import androidx.room.util.performSuspending
import androidx.sqlite.SQLiteStatement
import javax.`annotation`.processing.Generated
import kotlin.Int
import kotlin.String
import kotlin.Suppress
import kotlin.Unit
import kotlin.collections.List
import kotlin.reflect.KClass

@Generated(value = ["androidx.room.RoomProcessor"])
@Suppress(names = ["UNCHECKED_CAST", "DEPRECATION", "REDUNDANT_PROJECTION", "REMOVAL"])
public class SyncMetaDao_Impl(
  __db: RoomDatabase,
) : SyncMetaDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfSyncMetaEntity: EntityInsertAdapter<SyncMetaEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfSyncMetaEntity = object : EntityInsertAdapter<SyncMetaEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `sync_meta` (`key`,`value`) VALUES (?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: SyncMetaEntity) {
        statement.bindText(1, entity.key)
        statement.bindText(2, entity.value)
      }
    }
  }

  public override suspend fun `set`(entity: SyncMetaEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfSyncMetaEntity.insert(_connection, entity)
  }

  public override suspend fun `get`(key: String): String? {
    val _sql: String = "SELECT value FROM sync_meta WHERE `key` = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, key)
        val _result: String?
        if (_stmt.step()) {
          if (_stmt.isNull(0)) {
            _result = null
          } else {
            _result = _stmt.getText(0)
          }
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
