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
public class LocalDocumentDao_Impl(
  __db: RoomDatabase,
) : LocalDocumentDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfLocalDocumentEntity: EntityInsertAdapter<LocalDocumentEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfLocalDocumentEntity = object : EntityInsertAdapter<LocalDocumentEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `local_documents` (`clientUUID`,`serverId`,`companyId`,`clientServerId`,`type`,`status`,`series`,`number`,`numberFull`,`issueDate`,`dueDate`,`currency`,`subtotal`,`vatTotal`,`total`,`paidAmount`,`paymentStatus`,`notes`,`clientName`,`clientCui`,`clientEmail`,`efacturaStatus`,`efacturaError`,`itemsJSON`,`updatedAt`,`pendingSync`,`pendingIssue`,`isDeleted`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: LocalDocumentEntity) {
        statement.bindText(1, entity.clientUUID)
        val _tmpServerId: Int? = entity.serverId
        if (_tmpServerId == null) {
          statement.bindNull(2)
        } else {
          statement.bindLong(2, _tmpServerId.toLong())
        }
        statement.bindLong(3, entity.companyId.toLong())
        val _tmpClientServerId: Int? = entity.clientServerId
        if (_tmpClientServerId == null) {
          statement.bindNull(4)
        } else {
          statement.bindLong(4, _tmpClientServerId.toLong())
        }
        statement.bindText(5, entity.type)
        statement.bindText(6, entity.status)
        val _tmpSeries: String? = entity.series
        if (_tmpSeries == null) {
          statement.bindNull(7)
        } else {
          statement.bindText(7, _tmpSeries)
        }
        val _tmpNumber: Int? = entity.number
        if (_tmpNumber == null) {
          statement.bindNull(8)
        } else {
          statement.bindLong(8, _tmpNumber.toLong())
        }
        val _tmpNumberFull: String? = entity.numberFull
        if (_tmpNumberFull == null) {
          statement.bindNull(9)
        } else {
          statement.bindText(9, _tmpNumberFull)
        }
        statement.bindText(10, entity.issueDate)
        val _tmpDueDate: String? = entity.dueDate
        if (_tmpDueDate == null) {
          statement.bindNull(11)
        } else {
          statement.bindText(11, _tmpDueDate)
        }
        statement.bindText(12, entity.currency)
        statement.bindDouble(13, entity.subtotal)
        statement.bindDouble(14, entity.vatTotal)
        statement.bindDouble(15, entity.total)
        statement.bindDouble(16, entity.paidAmount)
        statement.bindText(17, entity.paymentStatus)
        val _tmpNotes: String? = entity.notes
        if (_tmpNotes == null) {
          statement.bindNull(18)
        } else {
          statement.bindText(18, _tmpNotes)
        }
        val _tmpClientName: String? = entity.clientName
        if (_tmpClientName == null) {
          statement.bindNull(19)
        } else {
          statement.bindText(19, _tmpClientName)
        }
        val _tmpClientCui: String? = entity.clientCui
        if (_tmpClientCui == null) {
          statement.bindNull(20)
        } else {
          statement.bindText(20, _tmpClientCui)
        }
        val _tmpClientEmail: String? = entity.clientEmail
        if (_tmpClientEmail == null) {
          statement.bindNull(21)
        } else {
          statement.bindText(21, _tmpClientEmail)
        }
        val _tmpEfacturaStatus: String? = entity.efacturaStatus
        if (_tmpEfacturaStatus == null) {
          statement.bindNull(22)
        } else {
          statement.bindText(22, _tmpEfacturaStatus)
        }
        val _tmpEfacturaError: String? = entity.efacturaError
        if (_tmpEfacturaError == null) {
          statement.bindNull(23)
        } else {
          statement.bindText(23, _tmpEfacturaError)
        }
        statement.bindText(24, entity.itemsJSON)
        statement.bindLong(25, entity.updatedAt)
        val _tmp: Int = if (entity.pendingSync) 1 else 0
        statement.bindLong(26, _tmp.toLong())
        val _tmp_1: Int = if (entity.pendingIssue) 1 else 0
        statement.bindLong(27, _tmp_1.toLong())
        val _tmp_2: Int = if (entity.isDeleted) 1 else 0
        statement.bindLong(28, _tmp_2.toLong())
      }
    }
  }

  public override suspend fun upsert(entity: LocalDocumentEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfLocalDocumentEntity.insert(_connection, entity)
  }

  public override fun observeAll(): Flow<List<LocalDocumentEntity>> {
    val _sql: String = "SELECT * FROM local_documents WHERE isDeleted = 0 ORDER BY updatedAt DESC"
    return createFlow(__db, false, arrayOf("local_documents")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfClientServerId: Int = getColumnIndexOrThrow(_stmt, "clientServerId")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfStatus: Int = getColumnIndexOrThrow(_stmt, "status")
        val _columnIndexOfSeries: Int = getColumnIndexOrThrow(_stmt, "series")
        val _columnIndexOfNumber: Int = getColumnIndexOrThrow(_stmt, "number")
        val _columnIndexOfNumberFull: Int = getColumnIndexOrThrow(_stmt, "numberFull")
        val _columnIndexOfIssueDate: Int = getColumnIndexOrThrow(_stmt, "issueDate")
        val _columnIndexOfDueDate: Int = getColumnIndexOrThrow(_stmt, "dueDate")
        val _columnIndexOfCurrency: Int = getColumnIndexOrThrow(_stmt, "currency")
        val _columnIndexOfSubtotal: Int = getColumnIndexOrThrow(_stmt, "subtotal")
        val _columnIndexOfVatTotal: Int = getColumnIndexOrThrow(_stmt, "vatTotal")
        val _columnIndexOfTotal: Int = getColumnIndexOrThrow(_stmt, "total")
        val _columnIndexOfPaidAmount: Int = getColumnIndexOrThrow(_stmt, "paidAmount")
        val _columnIndexOfPaymentStatus: Int = getColumnIndexOrThrow(_stmt, "paymentStatus")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfClientName: Int = getColumnIndexOrThrow(_stmt, "clientName")
        val _columnIndexOfClientCui: Int = getColumnIndexOrThrow(_stmt, "clientCui")
        val _columnIndexOfClientEmail: Int = getColumnIndexOrThrow(_stmt, "clientEmail")
        val _columnIndexOfEfacturaStatus: Int = getColumnIndexOrThrow(_stmt, "efacturaStatus")
        val _columnIndexOfEfacturaError: Int = getColumnIndexOrThrow(_stmt, "efacturaError")
        val _columnIndexOfItemsJSON: Int = getColumnIndexOrThrow(_stmt, "itemsJSON")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfPendingIssue: Int = getColumnIndexOrThrow(_stmt, "pendingIssue")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: MutableList<LocalDocumentEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalDocumentEntity
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
          val _tmpClientServerId: Int?
          if (_stmt.isNull(_columnIndexOfClientServerId)) {
            _tmpClientServerId = null
          } else {
            _tmpClientServerId = _stmt.getLong(_columnIndexOfClientServerId).toInt()
          }
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpStatus: String
          _tmpStatus = _stmt.getText(_columnIndexOfStatus)
          val _tmpSeries: String?
          if (_stmt.isNull(_columnIndexOfSeries)) {
            _tmpSeries = null
          } else {
            _tmpSeries = _stmt.getText(_columnIndexOfSeries)
          }
          val _tmpNumber: Int?
          if (_stmt.isNull(_columnIndexOfNumber)) {
            _tmpNumber = null
          } else {
            _tmpNumber = _stmt.getLong(_columnIndexOfNumber).toInt()
          }
          val _tmpNumberFull: String?
          if (_stmt.isNull(_columnIndexOfNumberFull)) {
            _tmpNumberFull = null
          } else {
            _tmpNumberFull = _stmt.getText(_columnIndexOfNumberFull)
          }
          val _tmpIssueDate: String
          _tmpIssueDate = _stmt.getText(_columnIndexOfIssueDate)
          val _tmpDueDate: String?
          if (_stmt.isNull(_columnIndexOfDueDate)) {
            _tmpDueDate = null
          } else {
            _tmpDueDate = _stmt.getText(_columnIndexOfDueDate)
          }
          val _tmpCurrency: String
          _tmpCurrency = _stmt.getText(_columnIndexOfCurrency)
          val _tmpSubtotal: Double
          _tmpSubtotal = _stmt.getDouble(_columnIndexOfSubtotal)
          val _tmpVatTotal: Double
          _tmpVatTotal = _stmt.getDouble(_columnIndexOfVatTotal)
          val _tmpTotal: Double
          _tmpTotal = _stmt.getDouble(_columnIndexOfTotal)
          val _tmpPaidAmount: Double
          _tmpPaidAmount = _stmt.getDouble(_columnIndexOfPaidAmount)
          val _tmpPaymentStatus: String
          _tmpPaymentStatus = _stmt.getText(_columnIndexOfPaymentStatus)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpClientName: String?
          if (_stmt.isNull(_columnIndexOfClientName)) {
            _tmpClientName = null
          } else {
            _tmpClientName = _stmt.getText(_columnIndexOfClientName)
          }
          val _tmpClientCui: String?
          if (_stmt.isNull(_columnIndexOfClientCui)) {
            _tmpClientCui = null
          } else {
            _tmpClientCui = _stmt.getText(_columnIndexOfClientCui)
          }
          val _tmpClientEmail: String?
          if (_stmt.isNull(_columnIndexOfClientEmail)) {
            _tmpClientEmail = null
          } else {
            _tmpClientEmail = _stmt.getText(_columnIndexOfClientEmail)
          }
          val _tmpEfacturaStatus: String?
          if (_stmt.isNull(_columnIndexOfEfacturaStatus)) {
            _tmpEfacturaStatus = null
          } else {
            _tmpEfacturaStatus = _stmt.getText(_columnIndexOfEfacturaStatus)
          }
          val _tmpEfacturaError: String?
          if (_stmt.isNull(_columnIndexOfEfacturaError)) {
            _tmpEfacturaError = null
          } else {
            _tmpEfacturaError = _stmt.getText(_columnIndexOfEfacturaError)
          }
          val _tmpItemsJSON: String
          _tmpItemsJSON = _stmt.getText(_columnIndexOfItemsJSON)
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpPendingIssue: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingIssue).toInt()
          _tmpPendingIssue = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _item = LocalDocumentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpClientServerId,_tmpType,_tmpStatus,_tmpSeries,_tmpNumber,_tmpNumberFull,_tmpIssueDate,_tmpDueDate,_tmpCurrency,_tmpSubtotal,_tmpVatTotal,_tmpTotal,_tmpPaidAmount,_tmpPaymentStatus,_tmpNotes,_tmpClientName,_tmpClientCui,_tmpClientEmail,_tmpEfacturaStatus,_tmpEfacturaError,_tmpItemsJSON,_tmpUpdatedAt,_tmpPendingSync,_tmpPendingIssue,_tmpIsDeleted)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override fun observeByType(type: String): Flow<List<LocalDocumentEntity>> {
    val _sql: String = "SELECT * FROM local_documents WHERE isDeleted = 0 AND type = ? ORDER BY updatedAt DESC"
    return createFlow(__db, false, arrayOf("local_documents")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, type)
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfClientServerId: Int = getColumnIndexOrThrow(_stmt, "clientServerId")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfStatus: Int = getColumnIndexOrThrow(_stmt, "status")
        val _columnIndexOfSeries: Int = getColumnIndexOrThrow(_stmt, "series")
        val _columnIndexOfNumber: Int = getColumnIndexOrThrow(_stmt, "number")
        val _columnIndexOfNumberFull: Int = getColumnIndexOrThrow(_stmt, "numberFull")
        val _columnIndexOfIssueDate: Int = getColumnIndexOrThrow(_stmt, "issueDate")
        val _columnIndexOfDueDate: Int = getColumnIndexOrThrow(_stmt, "dueDate")
        val _columnIndexOfCurrency: Int = getColumnIndexOrThrow(_stmt, "currency")
        val _columnIndexOfSubtotal: Int = getColumnIndexOrThrow(_stmt, "subtotal")
        val _columnIndexOfVatTotal: Int = getColumnIndexOrThrow(_stmt, "vatTotal")
        val _columnIndexOfTotal: Int = getColumnIndexOrThrow(_stmt, "total")
        val _columnIndexOfPaidAmount: Int = getColumnIndexOrThrow(_stmt, "paidAmount")
        val _columnIndexOfPaymentStatus: Int = getColumnIndexOrThrow(_stmt, "paymentStatus")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfClientName: Int = getColumnIndexOrThrow(_stmt, "clientName")
        val _columnIndexOfClientCui: Int = getColumnIndexOrThrow(_stmt, "clientCui")
        val _columnIndexOfClientEmail: Int = getColumnIndexOrThrow(_stmt, "clientEmail")
        val _columnIndexOfEfacturaStatus: Int = getColumnIndexOrThrow(_stmt, "efacturaStatus")
        val _columnIndexOfEfacturaError: Int = getColumnIndexOrThrow(_stmt, "efacturaError")
        val _columnIndexOfItemsJSON: Int = getColumnIndexOrThrow(_stmt, "itemsJSON")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfPendingIssue: Int = getColumnIndexOrThrow(_stmt, "pendingIssue")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: MutableList<LocalDocumentEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalDocumentEntity
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
          val _tmpClientServerId: Int?
          if (_stmt.isNull(_columnIndexOfClientServerId)) {
            _tmpClientServerId = null
          } else {
            _tmpClientServerId = _stmt.getLong(_columnIndexOfClientServerId).toInt()
          }
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpStatus: String
          _tmpStatus = _stmt.getText(_columnIndexOfStatus)
          val _tmpSeries: String?
          if (_stmt.isNull(_columnIndexOfSeries)) {
            _tmpSeries = null
          } else {
            _tmpSeries = _stmt.getText(_columnIndexOfSeries)
          }
          val _tmpNumber: Int?
          if (_stmt.isNull(_columnIndexOfNumber)) {
            _tmpNumber = null
          } else {
            _tmpNumber = _stmt.getLong(_columnIndexOfNumber).toInt()
          }
          val _tmpNumberFull: String?
          if (_stmt.isNull(_columnIndexOfNumberFull)) {
            _tmpNumberFull = null
          } else {
            _tmpNumberFull = _stmt.getText(_columnIndexOfNumberFull)
          }
          val _tmpIssueDate: String
          _tmpIssueDate = _stmt.getText(_columnIndexOfIssueDate)
          val _tmpDueDate: String?
          if (_stmt.isNull(_columnIndexOfDueDate)) {
            _tmpDueDate = null
          } else {
            _tmpDueDate = _stmt.getText(_columnIndexOfDueDate)
          }
          val _tmpCurrency: String
          _tmpCurrency = _stmt.getText(_columnIndexOfCurrency)
          val _tmpSubtotal: Double
          _tmpSubtotal = _stmt.getDouble(_columnIndexOfSubtotal)
          val _tmpVatTotal: Double
          _tmpVatTotal = _stmt.getDouble(_columnIndexOfVatTotal)
          val _tmpTotal: Double
          _tmpTotal = _stmt.getDouble(_columnIndexOfTotal)
          val _tmpPaidAmount: Double
          _tmpPaidAmount = _stmt.getDouble(_columnIndexOfPaidAmount)
          val _tmpPaymentStatus: String
          _tmpPaymentStatus = _stmt.getText(_columnIndexOfPaymentStatus)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpClientName: String?
          if (_stmt.isNull(_columnIndexOfClientName)) {
            _tmpClientName = null
          } else {
            _tmpClientName = _stmt.getText(_columnIndexOfClientName)
          }
          val _tmpClientCui: String?
          if (_stmt.isNull(_columnIndexOfClientCui)) {
            _tmpClientCui = null
          } else {
            _tmpClientCui = _stmt.getText(_columnIndexOfClientCui)
          }
          val _tmpClientEmail: String?
          if (_stmt.isNull(_columnIndexOfClientEmail)) {
            _tmpClientEmail = null
          } else {
            _tmpClientEmail = _stmt.getText(_columnIndexOfClientEmail)
          }
          val _tmpEfacturaStatus: String?
          if (_stmt.isNull(_columnIndexOfEfacturaStatus)) {
            _tmpEfacturaStatus = null
          } else {
            _tmpEfacturaStatus = _stmt.getText(_columnIndexOfEfacturaStatus)
          }
          val _tmpEfacturaError: String?
          if (_stmt.isNull(_columnIndexOfEfacturaError)) {
            _tmpEfacturaError = null
          } else {
            _tmpEfacturaError = _stmt.getText(_columnIndexOfEfacturaError)
          }
          val _tmpItemsJSON: String
          _tmpItemsJSON = _stmt.getText(_columnIndexOfItemsJSON)
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpPendingIssue: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingIssue).toInt()
          _tmpPendingIssue = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _item = LocalDocumentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpClientServerId,_tmpType,_tmpStatus,_tmpSeries,_tmpNumber,_tmpNumberFull,_tmpIssueDate,_tmpDueDate,_tmpCurrency,_tmpSubtotal,_tmpVatTotal,_tmpTotal,_tmpPaidAmount,_tmpPaymentStatus,_tmpNotes,_tmpClientName,_tmpClientCui,_tmpClientEmail,_tmpEfacturaStatus,_tmpEfacturaError,_tmpItemsJSON,_tmpUpdatedAt,_tmpPendingSync,_tmpPendingIssue,_tmpIsDeleted)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByUuid(uuid: String): LocalDocumentEntity? {
    val _sql: String = "SELECT * FROM local_documents WHERE clientUUID = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, uuid)
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfClientServerId: Int = getColumnIndexOrThrow(_stmt, "clientServerId")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfStatus: Int = getColumnIndexOrThrow(_stmt, "status")
        val _columnIndexOfSeries: Int = getColumnIndexOrThrow(_stmt, "series")
        val _columnIndexOfNumber: Int = getColumnIndexOrThrow(_stmt, "number")
        val _columnIndexOfNumberFull: Int = getColumnIndexOrThrow(_stmt, "numberFull")
        val _columnIndexOfIssueDate: Int = getColumnIndexOrThrow(_stmt, "issueDate")
        val _columnIndexOfDueDate: Int = getColumnIndexOrThrow(_stmt, "dueDate")
        val _columnIndexOfCurrency: Int = getColumnIndexOrThrow(_stmt, "currency")
        val _columnIndexOfSubtotal: Int = getColumnIndexOrThrow(_stmt, "subtotal")
        val _columnIndexOfVatTotal: Int = getColumnIndexOrThrow(_stmt, "vatTotal")
        val _columnIndexOfTotal: Int = getColumnIndexOrThrow(_stmt, "total")
        val _columnIndexOfPaidAmount: Int = getColumnIndexOrThrow(_stmt, "paidAmount")
        val _columnIndexOfPaymentStatus: Int = getColumnIndexOrThrow(_stmt, "paymentStatus")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfClientName: Int = getColumnIndexOrThrow(_stmt, "clientName")
        val _columnIndexOfClientCui: Int = getColumnIndexOrThrow(_stmt, "clientCui")
        val _columnIndexOfClientEmail: Int = getColumnIndexOrThrow(_stmt, "clientEmail")
        val _columnIndexOfEfacturaStatus: Int = getColumnIndexOrThrow(_stmt, "efacturaStatus")
        val _columnIndexOfEfacturaError: Int = getColumnIndexOrThrow(_stmt, "efacturaError")
        val _columnIndexOfItemsJSON: Int = getColumnIndexOrThrow(_stmt, "itemsJSON")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfPendingIssue: Int = getColumnIndexOrThrow(_stmt, "pendingIssue")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: LocalDocumentEntity?
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
          val _tmpClientServerId: Int?
          if (_stmt.isNull(_columnIndexOfClientServerId)) {
            _tmpClientServerId = null
          } else {
            _tmpClientServerId = _stmt.getLong(_columnIndexOfClientServerId).toInt()
          }
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpStatus: String
          _tmpStatus = _stmt.getText(_columnIndexOfStatus)
          val _tmpSeries: String?
          if (_stmt.isNull(_columnIndexOfSeries)) {
            _tmpSeries = null
          } else {
            _tmpSeries = _stmt.getText(_columnIndexOfSeries)
          }
          val _tmpNumber: Int?
          if (_stmt.isNull(_columnIndexOfNumber)) {
            _tmpNumber = null
          } else {
            _tmpNumber = _stmt.getLong(_columnIndexOfNumber).toInt()
          }
          val _tmpNumberFull: String?
          if (_stmt.isNull(_columnIndexOfNumberFull)) {
            _tmpNumberFull = null
          } else {
            _tmpNumberFull = _stmt.getText(_columnIndexOfNumberFull)
          }
          val _tmpIssueDate: String
          _tmpIssueDate = _stmt.getText(_columnIndexOfIssueDate)
          val _tmpDueDate: String?
          if (_stmt.isNull(_columnIndexOfDueDate)) {
            _tmpDueDate = null
          } else {
            _tmpDueDate = _stmt.getText(_columnIndexOfDueDate)
          }
          val _tmpCurrency: String
          _tmpCurrency = _stmt.getText(_columnIndexOfCurrency)
          val _tmpSubtotal: Double
          _tmpSubtotal = _stmt.getDouble(_columnIndexOfSubtotal)
          val _tmpVatTotal: Double
          _tmpVatTotal = _stmt.getDouble(_columnIndexOfVatTotal)
          val _tmpTotal: Double
          _tmpTotal = _stmt.getDouble(_columnIndexOfTotal)
          val _tmpPaidAmount: Double
          _tmpPaidAmount = _stmt.getDouble(_columnIndexOfPaidAmount)
          val _tmpPaymentStatus: String
          _tmpPaymentStatus = _stmt.getText(_columnIndexOfPaymentStatus)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpClientName: String?
          if (_stmt.isNull(_columnIndexOfClientName)) {
            _tmpClientName = null
          } else {
            _tmpClientName = _stmt.getText(_columnIndexOfClientName)
          }
          val _tmpClientCui: String?
          if (_stmt.isNull(_columnIndexOfClientCui)) {
            _tmpClientCui = null
          } else {
            _tmpClientCui = _stmt.getText(_columnIndexOfClientCui)
          }
          val _tmpClientEmail: String?
          if (_stmt.isNull(_columnIndexOfClientEmail)) {
            _tmpClientEmail = null
          } else {
            _tmpClientEmail = _stmt.getText(_columnIndexOfClientEmail)
          }
          val _tmpEfacturaStatus: String?
          if (_stmt.isNull(_columnIndexOfEfacturaStatus)) {
            _tmpEfacturaStatus = null
          } else {
            _tmpEfacturaStatus = _stmt.getText(_columnIndexOfEfacturaStatus)
          }
          val _tmpEfacturaError: String?
          if (_stmt.isNull(_columnIndexOfEfacturaError)) {
            _tmpEfacturaError = null
          } else {
            _tmpEfacturaError = _stmt.getText(_columnIndexOfEfacturaError)
          }
          val _tmpItemsJSON: String
          _tmpItemsJSON = _stmt.getText(_columnIndexOfItemsJSON)
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpPendingIssue: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingIssue).toInt()
          _tmpPendingIssue = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _result = LocalDocumentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpClientServerId,_tmpType,_tmpStatus,_tmpSeries,_tmpNumber,_tmpNumberFull,_tmpIssueDate,_tmpDueDate,_tmpCurrency,_tmpSubtotal,_tmpVatTotal,_tmpTotal,_tmpPaidAmount,_tmpPaymentStatus,_tmpNotes,_tmpClientName,_tmpClientCui,_tmpClientEmail,_tmpEfacturaStatus,_tmpEfacturaError,_tmpItemsJSON,_tmpUpdatedAt,_tmpPendingSync,_tmpPendingIssue,_tmpIsDeleted)
        } else {
          _result = null
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByServerId(serverId: Int): LocalDocumentEntity? {
    val _sql: String = "SELECT * FROM local_documents WHERE serverId = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindLong(_argIndex, serverId.toLong())
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfClientServerId: Int = getColumnIndexOrThrow(_stmt, "clientServerId")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfStatus: Int = getColumnIndexOrThrow(_stmt, "status")
        val _columnIndexOfSeries: Int = getColumnIndexOrThrow(_stmt, "series")
        val _columnIndexOfNumber: Int = getColumnIndexOrThrow(_stmt, "number")
        val _columnIndexOfNumberFull: Int = getColumnIndexOrThrow(_stmt, "numberFull")
        val _columnIndexOfIssueDate: Int = getColumnIndexOrThrow(_stmt, "issueDate")
        val _columnIndexOfDueDate: Int = getColumnIndexOrThrow(_stmt, "dueDate")
        val _columnIndexOfCurrency: Int = getColumnIndexOrThrow(_stmt, "currency")
        val _columnIndexOfSubtotal: Int = getColumnIndexOrThrow(_stmt, "subtotal")
        val _columnIndexOfVatTotal: Int = getColumnIndexOrThrow(_stmt, "vatTotal")
        val _columnIndexOfTotal: Int = getColumnIndexOrThrow(_stmt, "total")
        val _columnIndexOfPaidAmount: Int = getColumnIndexOrThrow(_stmt, "paidAmount")
        val _columnIndexOfPaymentStatus: Int = getColumnIndexOrThrow(_stmt, "paymentStatus")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfClientName: Int = getColumnIndexOrThrow(_stmt, "clientName")
        val _columnIndexOfClientCui: Int = getColumnIndexOrThrow(_stmt, "clientCui")
        val _columnIndexOfClientEmail: Int = getColumnIndexOrThrow(_stmt, "clientEmail")
        val _columnIndexOfEfacturaStatus: Int = getColumnIndexOrThrow(_stmt, "efacturaStatus")
        val _columnIndexOfEfacturaError: Int = getColumnIndexOrThrow(_stmt, "efacturaError")
        val _columnIndexOfItemsJSON: Int = getColumnIndexOrThrow(_stmt, "itemsJSON")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfPendingIssue: Int = getColumnIndexOrThrow(_stmt, "pendingIssue")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: LocalDocumentEntity?
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
          val _tmpClientServerId: Int?
          if (_stmt.isNull(_columnIndexOfClientServerId)) {
            _tmpClientServerId = null
          } else {
            _tmpClientServerId = _stmt.getLong(_columnIndexOfClientServerId).toInt()
          }
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpStatus: String
          _tmpStatus = _stmt.getText(_columnIndexOfStatus)
          val _tmpSeries: String?
          if (_stmt.isNull(_columnIndexOfSeries)) {
            _tmpSeries = null
          } else {
            _tmpSeries = _stmt.getText(_columnIndexOfSeries)
          }
          val _tmpNumber: Int?
          if (_stmt.isNull(_columnIndexOfNumber)) {
            _tmpNumber = null
          } else {
            _tmpNumber = _stmt.getLong(_columnIndexOfNumber).toInt()
          }
          val _tmpNumberFull: String?
          if (_stmt.isNull(_columnIndexOfNumberFull)) {
            _tmpNumberFull = null
          } else {
            _tmpNumberFull = _stmt.getText(_columnIndexOfNumberFull)
          }
          val _tmpIssueDate: String
          _tmpIssueDate = _stmt.getText(_columnIndexOfIssueDate)
          val _tmpDueDate: String?
          if (_stmt.isNull(_columnIndexOfDueDate)) {
            _tmpDueDate = null
          } else {
            _tmpDueDate = _stmt.getText(_columnIndexOfDueDate)
          }
          val _tmpCurrency: String
          _tmpCurrency = _stmt.getText(_columnIndexOfCurrency)
          val _tmpSubtotal: Double
          _tmpSubtotal = _stmt.getDouble(_columnIndexOfSubtotal)
          val _tmpVatTotal: Double
          _tmpVatTotal = _stmt.getDouble(_columnIndexOfVatTotal)
          val _tmpTotal: Double
          _tmpTotal = _stmt.getDouble(_columnIndexOfTotal)
          val _tmpPaidAmount: Double
          _tmpPaidAmount = _stmt.getDouble(_columnIndexOfPaidAmount)
          val _tmpPaymentStatus: String
          _tmpPaymentStatus = _stmt.getText(_columnIndexOfPaymentStatus)
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpClientName: String?
          if (_stmt.isNull(_columnIndexOfClientName)) {
            _tmpClientName = null
          } else {
            _tmpClientName = _stmt.getText(_columnIndexOfClientName)
          }
          val _tmpClientCui: String?
          if (_stmt.isNull(_columnIndexOfClientCui)) {
            _tmpClientCui = null
          } else {
            _tmpClientCui = _stmt.getText(_columnIndexOfClientCui)
          }
          val _tmpClientEmail: String?
          if (_stmt.isNull(_columnIndexOfClientEmail)) {
            _tmpClientEmail = null
          } else {
            _tmpClientEmail = _stmt.getText(_columnIndexOfClientEmail)
          }
          val _tmpEfacturaStatus: String?
          if (_stmt.isNull(_columnIndexOfEfacturaStatus)) {
            _tmpEfacturaStatus = null
          } else {
            _tmpEfacturaStatus = _stmt.getText(_columnIndexOfEfacturaStatus)
          }
          val _tmpEfacturaError: String?
          if (_stmt.isNull(_columnIndexOfEfacturaError)) {
            _tmpEfacturaError = null
          } else {
            _tmpEfacturaError = _stmt.getText(_columnIndexOfEfacturaError)
          }
          val _tmpItemsJSON: String
          _tmpItemsJSON = _stmt.getText(_columnIndexOfItemsJSON)
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpPendingIssue: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfPendingIssue).toInt()
          _tmpPendingIssue = _tmp_1 != 0
          val _tmpIsDeleted: Boolean
          val _tmp_2: Int
          _tmp_2 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_2 != 0
          _result = LocalDocumentEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpClientServerId,_tmpType,_tmpStatus,_tmpSeries,_tmpNumber,_tmpNumberFull,_tmpIssueDate,_tmpDueDate,_tmpCurrency,_tmpSubtotal,_tmpVatTotal,_tmpTotal,_tmpPaidAmount,_tmpPaymentStatus,_tmpNotes,_tmpClientName,_tmpClientCui,_tmpClientEmail,_tmpEfacturaStatus,_tmpEfacturaError,_tmpItemsJSON,_tmpUpdatedAt,_tmpPendingSync,_tmpPendingIssue,_tmpIsDeleted)
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
