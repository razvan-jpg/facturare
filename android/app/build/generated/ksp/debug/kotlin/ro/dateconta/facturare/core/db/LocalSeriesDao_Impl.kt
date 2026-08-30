package ro.dateconta.facturare.core.db

import androidx.room.EntityInsertAdapter
import androidx.room.RoomDatabase
import androidx.room.coroutines.createFlow
import androidx.room.util.getColumnIndexOrThrow
import androidx.room.util.performSuspending
import androidx.sqlite.SQLiteStatement
import javax.`annotation`.processing.Generated
import kotlin.Boolean
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
public class LocalSeriesDao_Impl(
  __db: RoomDatabase,
) : LocalSeriesDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfLocalSeriesEntity: EntityInsertAdapter<LocalSeriesEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfLocalSeriesEntity = object : EntityInsertAdapter<LocalSeriesEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `local_series` (`serverId`,`companyId`,`type`,`prefix`,`firstNumber`,`nextNumber`,`year`,`active`,`isDefault`,`updatedAt`) VALUES (?,?,?,?,?,?,?,?,?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: LocalSeriesEntity) {
        statement.bindLong(1, entity.serverId.toLong())
        statement.bindLong(2, entity.companyId.toLong())
        statement.bindText(3, entity.type)
        statement.bindText(4, entity.prefix)
        statement.bindLong(5, entity.firstNumber.toLong())
        statement.bindLong(6, entity.nextNumber.toLong())
        statement.bindLong(7, entity.year.toLong())
        val _tmp: Int = if (entity.active) 1 else 0
        statement.bindLong(8, _tmp.toLong())
        val _tmp_1: Int = if (entity.isDefault) 1 else 0
        statement.bindLong(9, _tmp_1.toLong())
        statement.bindLong(10, entity.updatedAt)
      }
    }
  }

  public override suspend fun upsert(entity: LocalSeriesEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfLocalSeriesEntity.insert(_connection, entity)
  }

  public override fun observeAll(): Flow<List<LocalSeriesEntity>> {
    val _sql: String = "SELECT * FROM local_series ORDER BY type, prefix"
    return createFlow(__db, false, arrayOf("local_series")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfPrefix: Int = getColumnIndexOrThrow(_stmt, "prefix")
        val _columnIndexOfFirstNumber: Int = getColumnIndexOrThrow(_stmt, "firstNumber")
        val _columnIndexOfNextNumber: Int = getColumnIndexOrThrow(_stmt, "nextNumber")
        val _columnIndexOfYear: Int = getColumnIndexOrThrow(_stmt, "year")
        val _columnIndexOfActive: Int = getColumnIndexOrThrow(_stmt, "active")
        val _columnIndexOfIsDefault: Int = getColumnIndexOrThrow(_stmt, "isDefault")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _result: MutableList<LocalSeriesEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalSeriesEntity
          val _tmpServerId: Int
          _tmpServerId = _stmt.getLong(_columnIndexOfServerId).toInt()
          val _tmpCompanyId: Int
          _tmpCompanyId = _stmt.getLong(_columnIndexOfCompanyId).toInt()
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpPrefix: String
          _tmpPrefix = _stmt.getText(_columnIndexOfPrefix)
          val _tmpFirstNumber: Int
          _tmpFirstNumber = _stmt.getLong(_columnIndexOfFirstNumber).toInt()
          val _tmpNextNumber: Int
          _tmpNextNumber = _stmt.getLong(_columnIndexOfNextNumber).toInt()
          val _tmpYear: Int
          _tmpYear = _stmt.getLong(_columnIndexOfYear).toInt()
          val _tmpActive: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfActive).toInt()
          _tmpActive = _tmp != 0
          val _tmpIsDefault: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfIsDefault).toInt()
          _tmpIsDefault = _tmp_1 != 0
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          _item = LocalSeriesEntity(_tmpServerId,_tmpCompanyId,_tmpType,_tmpPrefix,_tmpFirstNumber,_tmpNextNumber,_tmpYear,_tmpActive,_tmpIsDefault,_tmpUpdatedAt)
          _result.add(_item)
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
