package ro.dateconta.facturare

import android.content.Context
import androidx.room.Room
import ro.dateconta.facturare.core.api.ApiClient
import ro.dateconta.facturare.core.auth.AuthRepository
import ro.dateconta.facturare.core.auth.TokenStore
import ro.dateconta.facturare.core.db.FacturareDatabase
import ro.dateconta.facturare.core.subscription.SubscriptionRepository
import ro.dateconta.facturare.core.sync.SyncEngine

class AppContainer(context: Context) {
    val api = ApiClient()
    val tokenStore = TokenStore(context.applicationContext)
    val database: FacturareDatabase = Room.databaseBuilder(
        context.applicationContext,
        FacturareDatabase::class.java,
        "facturare.db",
    ).fallbackToDestructiveMigration().build()

    val auth = AuthRepository(api, tokenStore)
    val sync = SyncEngine(api, database)
    val subscription = SubscriptionRepository(auth)
}
