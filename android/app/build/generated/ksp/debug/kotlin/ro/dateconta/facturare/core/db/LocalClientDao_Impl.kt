package ro.dateconta.facturare.core.db

import androidx.room.EntityDeleteOrUpdateAdapter
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
public class LocalClientDao_Impl(
  __db: RoomDatabase,
) : LocalClientDao {
  private val __db: RoomDatabase

  private val __insertAdapterOfLocalClientEntity: EntityInsertAdapter<LocalClientEntity>

  private val __updateAdapterOfLocalClientEntity: EntityDeleteOrUpdateAdapter<LocalClientEntity>
  init {
    this.__db = __db
    this.__insertAdapterOfLocalClientEntity = object : EntityInsertAdapter<LocalClientEntity>() {
      protected override fun createQuery(): String = "INSERT OR REPLACE INTO `local_clients` (`clientUUID`,`serverId`,`companyId`,`name`,`type`,`cui`,`regCom`,`cnp`,`address`,`city`,`county`,`country`,`phone`,`email`,`iban`,`bankName`,`notes`,`openingBalance`,`openingBalanceDate`,`updatedAt`,`pendingSync`,`isDeleted`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"

      protected override fun bind(statement: SQLiteStatement, entity: LocalClientEntity) {
        statement.bindText(1, entity.clientUUID)
        val _tmpServerId: Int? = entity.serverId
        if (_tmpServerId == null) {
          statement.bindNull(2)
        } else {
          statement.bindLong(2, _tmpServerId.toLong())
        }
        statement.bindLong(3, entity.companyId.toLong())
        statement.bindText(4, entity.name)
        statement.bindText(5, entity.type)
        val _tmpCui: String? = entity.cui
        if (_tmpCui == null) {
          statement.bindNull(6)
        } else {
          statement.bindText(6, _tmpCui)
        }
        val _tmpRegCom: String? = entity.regCom
        if (_tmpRegCom == null) {
          statement.bindNull(7)
        } else {
          statement.bindText(7, _tmpRegCom)
        }
        val _tmpCnp: String? = entity.cnp
        if (_tmpCnp == null) {
          statement.bindNull(8)
        } else {
          statement.bindText(8, _tmpCnp)
        }
        val _tmpAddress: String? = entity.address
        if (_tmpAddress == null) {
          statement.bindNull(9)
        } else {
          statement.bindText(9, _tmpAddress)
        }
        val _tmpCity: String? = entity.city
        if (_tmpCity == null) {
          statement.bindNull(10)
        } else {
          statement.bindText(10, _tmpCity)
        }
        val _tmpCounty: String? = entity.county
        if (_tmpCounty == null) {
          statement.bindNull(11)
        } else {
          statement.bindText(11, _tmpCounty)
        }
        val _tmpCountry: String? = entity.country
        if (_tmpCountry == null) {
          statement.bindNull(12)
        } else {
          statement.bindText(12, _tmpCountry)
        }
        val _tmpPhone: String? = entity.phone
        if (_tmpPhone == null) {
          statement.bindNull(13)
        } else {
          statement.bindText(13, _tmpPhone)
        }
        val _tmpEmail: String? = entity.email
        if (_tmpEmail == null) {
          statement.bindNull(14)
        } else {
          statement.bindText(14, _tmpEmail)
        }
        val _tmpIban: String? = entity.iban
        if (_tmpIban == null) {
          statement.bindNull(15)
        } else {
          statement.bindText(15, _tmpIban)
        }
        val _tmpBankName: String? = entity.bankName
        if (_tmpBankName == null) {
          statement.bindNull(16)
        } else {
          statement.bindText(16, _tmpBankName)
        }
        val _tmpNotes: String? = entity.notes
        if (_tmpNotes == null) {
          statement.bindNull(17)
        } else {
          statement.bindText(17, _tmpNotes)
        }
        statement.bindDouble(18, entity.openingBalance)
        val _tmpOpeningBalanceDate: String? = entity.openingBalanceDate
        if (_tmpOpeningBalanceDate == null) {
          statement.bindNull(19)
        } else {
          statement.bindText(19, _tmpOpeningBalanceDate)
        }
        statement.bindLong(20, entity.updatedAt)
        val _tmp: Int = if (entity.pendingSync) 1 else 0
        statement.bindLong(21, _tmp.toLong())
        val _tmp_1: Int = if (entity.isDeleted) 1 else 0
        statement.bindLong(22, _tmp_1.toLong())
      }
    }
    this.__updateAdapterOfLocalClientEntity = object : EntityDeleteOrUpdateAdapter<LocalClientEntity>() {
      protected override fun createQuery(): String = "UPDATE OR ABORT `local_clients` SET `clientUUID` = ?,`serverId` = ?,`companyId` = ?,`name` = ?,`type` = ?,`cui` = ?,`regCom` = ?,`cnp` = ?,`address` = ?,`city` = ?,`county` = ?,`country` = ?,`phone` = ?,`email` = ?,`iban` = ?,`bankName` = ?,`notes` = ?,`openingBalance` = ?,`openingBalanceDate` = ?,`updatedAt` = ?,`pendingSync` = ?,`isDeleted` = ? WHERE `clientUUID` = ?"

      protected override fun bind(statement: SQLiteStatement, entity: LocalClientEntity) {
        statement.bindText(1, entity.clientUUID)
        val _tmpServerId: Int? = entity.serverId
        if (_tmpServerId == null) {
          statement.bindNull(2)
        } else {
          statement.bindLong(2, _tmpServerId.toLong())
        }
        statement.bindLong(3, entity.companyId.toLong())
        statement.bindText(4, entity.name)
        statement.bindText(5, entity.type)
        val _tmpCui: String? = entity.cui
        if (_tmpCui == null) {
          statement.bindNull(6)
        } else {
          statement.bindText(6, _tmpCui)
        }
        val _tmpRegCom: String? = entity.regCom
        if (_tmpRegCom == null) {
          statement.bindNull(7)
        } else {
          statement.bindText(7, _tmpRegCom)
        }
        val _tmpCnp: String? = entity.cnp
        if (_tmpCnp == null) {
          statement.bindNull(8)
        } else {
          statement.bindText(8, _tmpCnp)
        }
        val _tmpAddress: String? = entity.address
        if (_tmpAddress == null) {
          statement.bindNull(9)
        } else {
          statement.bindText(9, _tmpAddress)
        }
        val _tmpCity: String? = entity.city
        if (_tmpCity == null) {
          statement.bindNull(10)
        } else {
          statement.bindText(10, _tmpCity)
        }
        val _tmpCounty: String? = entity.county
        if (_tmpCounty == null) {
          statement.bindNull(11)
        } else {
          statement.bindText(11, _tmpCounty)
        }
        val _tmpCountry: String? = entity.country
        if (_tmpCountry == null) {
          statement.bindNull(12)
        } else {
          statement.bindText(12, _tmpCountry)
        }
        val _tmpPhone: String? = entity.phone
        if (_tmpPhone == null) {
          statement.bindNull(13)
        } else {
          statement.bindText(13, _tmpPhone)
        }
        val _tmpEmail: String? = entity.email
        if (_tmpEmail == null) {
          statement.bindNull(14)
        } else {
          statement.bindText(14, _tmpEmail)
        }
        val _tmpIban: String? = entity.iban
        if (_tmpIban == null) {
          statement.bindNull(15)
        } else {
          statement.bindText(15, _tmpIban)
        }
        val _tmpBankName: String? = entity.bankName
        if (_tmpBankName == null) {
          statement.bindNull(16)
        } else {
          statement.bindText(16, _tmpBankName)
        }
        val _tmpNotes: String? = entity.notes
        if (_tmpNotes == null) {
          statement.bindNull(17)
        } else {
          statement.bindText(17, _tmpNotes)
        }
        statement.bindDouble(18, entity.openingBalance)
        val _tmpOpeningBalanceDate: String? = entity.openingBalanceDate
        if (_tmpOpeningBalanceDate == null) {
          statement.bindNull(19)
        } else {
          statement.bindText(19, _tmpOpeningBalanceDate)
        }
        statement.bindLong(20, entity.updatedAt)
        val _tmp: Int = if (entity.pendingSync) 1 else 0
        statement.bindLong(21, _tmp.toLong())
        val _tmp_1: Int = if (entity.isDeleted) 1 else 0
        statement.bindLong(22, _tmp_1.toLong())
        statement.bindText(23, entity.clientUUID)
      }
    }
  }

  public override suspend fun upsert(entity: LocalClientEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __insertAdapterOfLocalClientEntity.insert(_connection, entity)
  }

  public override suspend fun update(entity: LocalClientEntity): Unit = performSuspending(__db, false, true) { _connection ->
    __updateAdapterOfLocalClientEntity.handle(_connection, entity)
  }

  public override fun observeAll(): Flow<List<LocalClientEntity>> {
    val _sql: String = "SELECT * FROM local_clients WHERE isDeleted = 0 ORDER BY name"
    return createFlow(__db, false, arrayOf("local_clients")) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfCui: Int = getColumnIndexOrThrow(_stmt, "cui")
        val _columnIndexOfRegCom: Int = getColumnIndexOrThrow(_stmt, "regCom")
        val _columnIndexOfCnp: Int = getColumnIndexOrThrow(_stmt, "cnp")
        val _columnIndexOfAddress: Int = getColumnIndexOrThrow(_stmt, "address")
        val _columnIndexOfCity: Int = getColumnIndexOrThrow(_stmt, "city")
        val _columnIndexOfCounty: Int = getColumnIndexOrThrow(_stmt, "county")
        val _columnIndexOfCountry: Int = getColumnIndexOrThrow(_stmt, "country")
        val _columnIndexOfPhone: Int = getColumnIndexOrThrow(_stmt, "phone")
        val _columnIndexOfEmail: Int = getColumnIndexOrThrow(_stmt, "email")
        val _columnIndexOfIban: Int = getColumnIndexOrThrow(_stmt, "iban")
        val _columnIndexOfBankName: Int = getColumnIndexOrThrow(_stmt, "bankName")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfOpeningBalance: Int = getColumnIndexOrThrow(_stmt, "openingBalance")
        val _columnIndexOfOpeningBalanceDate: Int = getColumnIndexOrThrow(_stmt, "openingBalanceDate")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: MutableList<LocalClientEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalClientEntity
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
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpCui: String?
          if (_stmt.isNull(_columnIndexOfCui)) {
            _tmpCui = null
          } else {
            _tmpCui = _stmt.getText(_columnIndexOfCui)
          }
          val _tmpRegCom: String?
          if (_stmt.isNull(_columnIndexOfRegCom)) {
            _tmpRegCom = null
          } else {
            _tmpRegCom = _stmt.getText(_columnIndexOfRegCom)
          }
          val _tmpCnp: String?
          if (_stmt.isNull(_columnIndexOfCnp)) {
            _tmpCnp = null
          } else {
            _tmpCnp = _stmt.getText(_columnIndexOfCnp)
          }
          val _tmpAddress: String?
          if (_stmt.isNull(_columnIndexOfAddress)) {
            _tmpAddress = null
          } else {
            _tmpAddress = _stmt.getText(_columnIndexOfAddress)
          }
          val _tmpCity: String?
          if (_stmt.isNull(_columnIndexOfCity)) {
            _tmpCity = null
          } else {
            _tmpCity = _stmt.getText(_columnIndexOfCity)
          }
          val _tmpCounty: String?
          if (_stmt.isNull(_columnIndexOfCounty)) {
            _tmpCounty = null
          } else {
            _tmpCounty = _stmt.getText(_columnIndexOfCounty)
          }
          val _tmpCountry: String?
          if (_stmt.isNull(_columnIndexOfCountry)) {
            _tmpCountry = null
          } else {
            _tmpCountry = _stmt.getText(_columnIndexOfCountry)
          }
          val _tmpPhone: String?
          if (_stmt.isNull(_columnIndexOfPhone)) {
            _tmpPhone = null
          } else {
            _tmpPhone = _stmt.getText(_columnIndexOfPhone)
          }
          val _tmpEmail: String?
          if (_stmt.isNull(_columnIndexOfEmail)) {
            _tmpEmail = null
          } else {
            _tmpEmail = _stmt.getText(_columnIndexOfEmail)
          }
          val _tmpIban: String?
          if (_stmt.isNull(_columnIndexOfIban)) {
            _tmpIban = null
          } else {
            _tmpIban = _stmt.getText(_columnIndexOfIban)
          }
          val _tmpBankName: String?
          if (_stmt.isNull(_columnIndexOfBankName)) {
            _tmpBankName = null
          } else {
            _tmpBankName = _stmt.getText(_columnIndexOfBankName)
          }
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpOpeningBalance: Double
          _tmpOpeningBalance = _stmt.getDouble(_columnIndexOfOpeningBalance)
          val _tmpOpeningBalanceDate: String?
          if (_stmt.isNull(_columnIndexOfOpeningBalanceDate)) {
            _tmpOpeningBalanceDate = null
          } else {
            _tmpOpeningBalanceDate = _stmt.getText(_columnIndexOfOpeningBalanceDate)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpIsDeleted: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_1 != 0
          _item = LocalClientEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpType,_tmpCui,_tmpRegCom,_tmpCnp,_tmpAddress,_tmpCity,_tmpCounty,_tmpCountry,_tmpPhone,_tmpEmail,_tmpIban,_tmpBankName,_tmpNotes,_tmpOpeningBalance,_tmpOpeningBalanceDate,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getAll(): List<LocalClientEntity> {
    val _sql: String = "SELECT * FROM local_clients WHERE isDeleted = 0 ORDER BY name"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfCui: Int = getColumnIndexOrThrow(_stmt, "cui")
        val _columnIndexOfRegCom: Int = getColumnIndexOrThrow(_stmt, "regCom")
        val _columnIndexOfCnp: Int = getColumnIndexOrThrow(_stmt, "cnp")
        val _columnIndexOfAddress: Int = getColumnIndexOrThrow(_stmt, "address")
        val _columnIndexOfCity: Int = getColumnIndexOrThrow(_stmt, "city")
        val _columnIndexOfCounty: Int = getColumnIndexOrThrow(_stmt, "county")
        val _columnIndexOfCountry: Int = getColumnIndexOrThrow(_stmt, "country")
        val _columnIndexOfPhone: Int = getColumnIndexOrThrow(_stmt, "phone")
        val _columnIndexOfEmail: Int = getColumnIndexOrThrow(_stmt, "email")
        val _columnIndexOfIban: Int = getColumnIndexOrThrow(_stmt, "iban")
        val _columnIndexOfBankName: Int = getColumnIndexOrThrow(_stmt, "bankName")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfOpeningBalance: Int = getColumnIndexOrThrow(_stmt, "openingBalance")
        val _columnIndexOfOpeningBalanceDate: Int = getColumnIndexOrThrow(_stmt, "openingBalanceDate")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: MutableList<LocalClientEntity> = mutableListOf()
        while (_stmt.step()) {
          val _item: LocalClientEntity
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
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpCui: String?
          if (_stmt.isNull(_columnIndexOfCui)) {
            _tmpCui = null
          } else {
            _tmpCui = _stmt.getText(_columnIndexOfCui)
          }
          val _tmpRegCom: String?
          if (_stmt.isNull(_columnIndexOfRegCom)) {
            _tmpRegCom = null
          } else {
            _tmpRegCom = _stmt.getText(_columnIndexOfRegCom)
          }
          val _tmpCnp: String?
          if (_stmt.isNull(_columnIndexOfCnp)) {
            _tmpCnp = null
          } else {
            _tmpCnp = _stmt.getText(_columnIndexOfCnp)
          }
          val _tmpAddress: String?
          if (_stmt.isNull(_columnIndexOfAddress)) {
            _tmpAddress = null
          } else {
            _tmpAddress = _stmt.getText(_columnIndexOfAddress)
          }
          val _tmpCity: String?
          if (_stmt.isNull(_columnIndexOfCity)) {
            _tmpCity = null
          } else {
            _tmpCity = _stmt.getText(_columnIndexOfCity)
          }
          val _tmpCounty: String?
          if (_stmt.isNull(_columnIndexOfCounty)) {
            _tmpCounty = null
          } else {
            _tmpCounty = _stmt.getText(_columnIndexOfCounty)
          }
          val _tmpCountry: String?
          if (_stmt.isNull(_columnIndexOfCountry)) {
            _tmpCountry = null
          } else {
            _tmpCountry = _stmt.getText(_columnIndexOfCountry)
          }
          val _tmpPhone: String?
          if (_stmt.isNull(_columnIndexOfPhone)) {
            _tmpPhone = null
          } else {
            _tmpPhone = _stmt.getText(_columnIndexOfPhone)
          }
          val _tmpEmail: String?
          if (_stmt.isNull(_columnIndexOfEmail)) {
            _tmpEmail = null
          } else {
            _tmpEmail = _stmt.getText(_columnIndexOfEmail)
          }
          val _tmpIban: String?
          if (_stmt.isNull(_columnIndexOfIban)) {
            _tmpIban = null
          } else {
            _tmpIban = _stmt.getText(_columnIndexOfIban)
          }
          val _tmpBankName: String?
          if (_stmt.isNull(_columnIndexOfBankName)) {
            _tmpBankName = null
          } else {
            _tmpBankName = _stmt.getText(_columnIndexOfBankName)
          }
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpOpeningBalance: Double
          _tmpOpeningBalance = _stmt.getDouble(_columnIndexOfOpeningBalance)
          val _tmpOpeningBalanceDate: String?
          if (_stmt.isNull(_columnIndexOfOpeningBalanceDate)) {
            _tmpOpeningBalanceDate = null
          } else {
            _tmpOpeningBalanceDate = _stmt.getText(_columnIndexOfOpeningBalanceDate)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpIsDeleted: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_1 != 0
          _item = LocalClientEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpType,_tmpCui,_tmpRegCom,_tmpCnp,_tmpAddress,_tmpCity,_tmpCounty,_tmpCountry,_tmpPhone,_tmpEmail,_tmpIban,_tmpBankName,_tmpNotes,_tmpOpeningBalance,_tmpOpeningBalanceDate,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
          _result.add(_item)
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByUuid(uuid: String): LocalClientEntity? {
    val _sql: String = "SELECT * FROM local_clients WHERE clientUUID = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindText(_argIndex, uuid)
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfCui: Int = getColumnIndexOrThrow(_stmt, "cui")
        val _columnIndexOfRegCom: Int = getColumnIndexOrThrow(_stmt, "regCom")
        val _columnIndexOfCnp: Int = getColumnIndexOrThrow(_stmt, "cnp")
        val _columnIndexOfAddress: Int = getColumnIndexOrThrow(_stmt, "address")
        val _columnIndexOfCity: Int = getColumnIndexOrThrow(_stmt, "city")
        val _columnIndexOfCounty: Int = getColumnIndexOrThrow(_stmt, "county")
        val _columnIndexOfCountry: Int = getColumnIndexOrThrow(_stmt, "country")
        val _columnIndexOfPhone: Int = getColumnIndexOrThrow(_stmt, "phone")
        val _columnIndexOfEmail: Int = getColumnIndexOrThrow(_stmt, "email")
        val _columnIndexOfIban: Int = getColumnIndexOrThrow(_stmt, "iban")
        val _columnIndexOfBankName: Int = getColumnIndexOrThrow(_stmt, "bankName")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfOpeningBalance: Int = getColumnIndexOrThrow(_stmt, "openingBalance")
        val _columnIndexOfOpeningBalanceDate: Int = getColumnIndexOrThrow(_stmt, "openingBalanceDate")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: LocalClientEntity?
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
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpCui: String?
          if (_stmt.isNull(_columnIndexOfCui)) {
            _tmpCui = null
          } else {
            _tmpCui = _stmt.getText(_columnIndexOfCui)
          }
          val _tmpRegCom: String?
          if (_stmt.isNull(_columnIndexOfRegCom)) {
            _tmpRegCom = null
          } else {
            _tmpRegCom = _stmt.getText(_columnIndexOfRegCom)
          }
          val _tmpCnp: String?
          if (_stmt.isNull(_columnIndexOfCnp)) {
            _tmpCnp = null
          } else {
            _tmpCnp = _stmt.getText(_columnIndexOfCnp)
          }
          val _tmpAddress: String?
          if (_stmt.isNull(_columnIndexOfAddress)) {
            _tmpAddress = null
          } else {
            _tmpAddress = _stmt.getText(_columnIndexOfAddress)
          }
          val _tmpCity: String?
          if (_stmt.isNull(_columnIndexOfCity)) {
            _tmpCity = null
          } else {
            _tmpCity = _stmt.getText(_columnIndexOfCity)
          }
          val _tmpCounty: String?
          if (_stmt.isNull(_columnIndexOfCounty)) {
            _tmpCounty = null
          } else {
            _tmpCounty = _stmt.getText(_columnIndexOfCounty)
          }
          val _tmpCountry: String?
          if (_stmt.isNull(_columnIndexOfCountry)) {
            _tmpCountry = null
          } else {
            _tmpCountry = _stmt.getText(_columnIndexOfCountry)
          }
          val _tmpPhone: String?
          if (_stmt.isNull(_columnIndexOfPhone)) {
            _tmpPhone = null
          } else {
            _tmpPhone = _stmt.getText(_columnIndexOfPhone)
          }
          val _tmpEmail: String?
          if (_stmt.isNull(_columnIndexOfEmail)) {
            _tmpEmail = null
          } else {
            _tmpEmail = _stmt.getText(_columnIndexOfEmail)
          }
          val _tmpIban: String?
          if (_stmt.isNull(_columnIndexOfIban)) {
            _tmpIban = null
          } else {
            _tmpIban = _stmt.getText(_columnIndexOfIban)
          }
          val _tmpBankName: String?
          if (_stmt.isNull(_columnIndexOfBankName)) {
            _tmpBankName = null
          } else {
            _tmpBankName = _stmt.getText(_columnIndexOfBankName)
          }
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpOpeningBalance: Double
          _tmpOpeningBalance = _stmt.getDouble(_columnIndexOfOpeningBalance)
          val _tmpOpeningBalanceDate: String?
          if (_stmt.isNull(_columnIndexOfOpeningBalanceDate)) {
            _tmpOpeningBalanceDate = null
          } else {
            _tmpOpeningBalanceDate = _stmt.getText(_columnIndexOfOpeningBalanceDate)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpIsDeleted: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_1 != 0
          _result = LocalClientEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpType,_tmpCui,_tmpRegCom,_tmpCnp,_tmpAddress,_tmpCity,_tmpCounty,_tmpCountry,_tmpPhone,_tmpEmail,_tmpIban,_tmpBankName,_tmpNotes,_tmpOpeningBalance,_tmpOpeningBalanceDate,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
        } else {
          _result = null
        }
        _result
      } finally {
        _stmt.close()
      }
    }
  }

  public override suspend fun getByServerId(serverId: Int): LocalClientEntity? {
    val _sql: String = "SELECT * FROM local_clients WHERE serverId = ? LIMIT 1"
    return performSuspending(__db, true, false) { _connection ->
      val _stmt: SQLiteStatement = _connection.prepare(_sql)
      try {
        var _argIndex: Int = 1
        _stmt.bindLong(_argIndex, serverId.toLong())
        val _columnIndexOfClientUUID: Int = getColumnIndexOrThrow(_stmt, "clientUUID")
        val _columnIndexOfServerId: Int = getColumnIndexOrThrow(_stmt, "serverId")
        val _columnIndexOfCompanyId: Int = getColumnIndexOrThrow(_stmt, "companyId")
        val _columnIndexOfName: Int = getColumnIndexOrThrow(_stmt, "name")
        val _columnIndexOfType: Int = getColumnIndexOrThrow(_stmt, "type")
        val _columnIndexOfCui: Int = getColumnIndexOrThrow(_stmt, "cui")
        val _columnIndexOfRegCom: Int = getColumnIndexOrThrow(_stmt, "regCom")
        val _columnIndexOfCnp: Int = getColumnIndexOrThrow(_stmt, "cnp")
        val _columnIndexOfAddress: Int = getColumnIndexOrThrow(_stmt, "address")
        val _columnIndexOfCity: Int = getColumnIndexOrThrow(_stmt, "city")
        val _columnIndexOfCounty: Int = getColumnIndexOrThrow(_stmt, "county")
        val _columnIndexOfCountry: Int = getColumnIndexOrThrow(_stmt, "country")
        val _columnIndexOfPhone: Int = getColumnIndexOrThrow(_stmt, "phone")
        val _columnIndexOfEmail: Int = getColumnIndexOrThrow(_stmt, "email")
        val _columnIndexOfIban: Int = getColumnIndexOrThrow(_stmt, "iban")
        val _columnIndexOfBankName: Int = getColumnIndexOrThrow(_stmt, "bankName")
        val _columnIndexOfNotes: Int = getColumnIndexOrThrow(_stmt, "notes")
        val _columnIndexOfOpeningBalance: Int = getColumnIndexOrThrow(_stmt, "openingBalance")
        val _columnIndexOfOpeningBalanceDate: Int = getColumnIndexOrThrow(_stmt, "openingBalanceDate")
        val _columnIndexOfUpdatedAt: Int = getColumnIndexOrThrow(_stmt, "updatedAt")
        val _columnIndexOfPendingSync: Int = getColumnIndexOrThrow(_stmt, "pendingSync")
        val _columnIndexOfIsDeleted: Int = getColumnIndexOrThrow(_stmt, "isDeleted")
        val _result: LocalClientEntity?
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
          val _tmpType: String
          _tmpType = _stmt.getText(_columnIndexOfType)
          val _tmpCui: String?
          if (_stmt.isNull(_columnIndexOfCui)) {
            _tmpCui = null
          } else {
            _tmpCui = _stmt.getText(_columnIndexOfCui)
          }
          val _tmpRegCom: String?
          if (_stmt.isNull(_columnIndexOfRegCom)) {
            _tmpRegCom = null
          } else {
            _tmpRegCom = _stmt.getText(_columnIndexOfRegCom)
          }
          val _tmpCnp: String?
          if (_stmt.isNull(_columnIndexOfCnp)) {
            _tmpCnp = null
          } else {
            _tmpCnp = _stmt.getText(_columnIndexOfCnp)
          }
          val _tmpAddress: String?
          if (_stmt.isNull(_columnIndexOfAddress)) {
            _tmpAddress = null
          } else {
            _tmpAddress = _stmt.getText(_columnIndexOfAddress)
          }
          val _tmpCity: String?
          if (_stmt.isNull(_columnIndexOfCity)) {
            _tmpCity = null
          } else {
            _tmpCity = _stmt.getText(_columnIndexOfCity)
          }
          val _tmpCounty: String?
          if (_stmt.isNull(_columnIndexOfCounty)) {
            _tmpCounty = null
          } else {
            _tmpCounty = _stmt.getText(_columnIndexOfCounty)
          }
          val _tmpCountry: String?
          if (_stmt.isNull(_columnIndexOfCountry)) {
            _tmpCountry = null
          } else {
            _tmpCountry = _stmt.getText(_columnIndexOfCountry)
          }
          val _tmpPhone: String?
          if (_stmt.isNull(_columnIndexOfPhone)) {
            _tmpPhone = null
          } else {
            _tmpPhone = _stmt.getText(_columnIndexOfPhone)
          }
          val _tmpEmail: String?
          if (_stmt.isNull(_columnIndexOfEmail)) {
            _tmpEmail = null
          } else {
            _tmpEmail = _stmt.getText(_columnIndexOfEmail)
          }
          val _tmpIban: String?
          if (_stmt.isNull(_columnIndexOfIban)) {
            _tmpIban = null
          } else {
            _tmpIban = _stmt.getText(_columnIndexOfIban)
          }
          val _tmpBankName: String?
          if (_stmt.isNull(_columnIndexOfBankName)) {
            _tmpBankName = null
          } else {
            _tmpBankName = _stmt.getText(_columnIndexOfBankName)
          }
          val _tmpNotes: String?
          if (_stmt.isNull(_columnIndexOfNotes)) {
            _tmpNotes = null
          } else {
            _tmpNotes = _stmt.getText(_columnIndexOfNotes)
          }
          val _tmpOpeningBalance: Double
          _tmpOpeningBalance = _stmt.getDouble(_columnIndexOfOpeningBalance)
          val _tmpOpeningBalanceDate: String?
          if (_stmt.isNull(_columnIndexOfOpeningBalanceDate)) {
            _tmpOpeningBalanceDate = null
          } else {
            _tmpOpeningBalanceDate = _stmt.getText(_columnIndexOfOpeningBalanceDate)
          }
          val _tmpUpdatedAt: Long
          _tmpUpdatedAt = _stmt.getLong(_columnIndexOfUpdatedAt)
          val _tmpPendingSync: Boolean
          val _tmp: Int
          _tmp = _stmt.getLong(_columnIndexOfPendingSync).toInt()
          _tmpPendingSync = _tmp != 0
          val _tmpIsDeleted: Boolean
          val _tmp_1: Int
          _tmp_1 = _stmt.getLong(_columnIndexOfIsDeleted).toInt()
          _tmpIsDeleted = _tmp_1 != 0
          _result = LocalClientEntity(_tmpClientUUID,_tmpServerId,_tmpCompanyId,_tmpName,_tmpType,_tmpCui,_tmpRegCom,_tmpCnp,_tmpAddress,_tmpCity,_tmpCounty,_tmpCountry,_tmpPhone,_tmpEmail,_tmpIban,_tmpBankName,_tmpNotes,_tmpOpeningBalance,_tmpOpeningBalanceDate,_tmpUpdatedAt,_tmpPendingSync,_tmpIsDeleted)
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
