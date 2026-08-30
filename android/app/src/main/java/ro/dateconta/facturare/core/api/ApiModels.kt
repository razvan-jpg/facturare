package ro.dateconta.facturare.core.api

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement

@Serializable
data class AuthResponse(
    val token: String? = null,
    @SerialName("token_type") val tokenType: String? = null,
    val user: ApiUser,
    val companies: List<ApiCompany> = emptyList(),
    @SerialName("app_version") val appVersion: String? = null,
)

@Serializable
data class MeResponse(
    val user: ApiUser,
    val companies: List<ApiCompany> = emptyList(),
    @SerialName("app_version") val appVersion: String? = null,
)

@Serializable
data class ApiUser(
    val id: Int,
    val name: String,
    val email: String,
    val plan: String? = null,
    @SerialName("ui_locale") val uiLocale: String? = null,
    @SerialName("is_admin") val isAdmin: Boolean? = null,
    @SerialName("has_access") val hasAccess: Boolean? = null,
    @SerialName("access_label") val accessLabel: String? = null,
    @SerialName("current_company_id") val currentCompanyId: Int? = null,
    @SerialName("can_manage_company_users") val canManageCompanyUsers: Boolean? = null,
)

@Serializable
data class ApiCompany(
    val id: Int,
    val name: String,
    val cui: String? = null,
    @SerialName("promo_code") val promoCode: String? = null,
    @SerialName("reg_com") val regCom: String? = null,
    val address: String? = null,
    val city: String? = null,
    val county: String? = null,
    val country: String? = null,
    val phone: String? = null,
    val email: String? = null,
    val iban: String? = null,
    @SerialName("bank_name") val bankName: String? = null,
    @SerialName("vat_payer") val vatPayer: Boolean? = null,
    @SerialName("default_vat_rate") val defaultVatRate: Double? = null,
    @SerialName("efactura_send_mode") val efacturaSendMode: String? = null,
    @SerialName("anaf_authorized") val anafAuthorized: Boolean? = null,
    val role: String? = null,
    val permissions: List<String>? = null,
    @SerialName("updated_at") val updatedAt: String? = null,
) {
    fun can(ability: String): Boolean {
        if (role == "owner") return true
        return permissions?.contains(ability) == true
    }
}

@Serializable
data class ApiClientDto(
    val id: Int,
    @SerialName("company_id") val companyId: Int? = null,
    val name: String,
    val type: String? = null,
    val cui: String? = null,
    @SerialName("reg_com") val regCom: String? = null,
    val cnp: String? = null,
    val address: String? = null,
    val city: String? = null,
    val county: String? = null,
    val country: String? = null,
    val phone: String? = null,
    val email: String? = null,
    val iban: String? = null,
    @SerialName("bank_name") val bankName: String? = null,
    val notes: String? = null,
    @SerialName("opening_balance") val openingBalance: Double? = null,
    @SerialName("opening_balance_date") val openingBalanceDate: String? = null,
    @SerialName("updated_at") val updatedAt: String? = null,
)

@Serializable
data class ApiProduct(
    val id: Int,
    @SerialName("company_id") val companyId: Int? = null,
    val name: String,
    val sku: String? = null,
    val unit: String? = null,
    val type: String? = null,
    val price: Double = 0.0,
    @SerialName("vat_rate") val vatRate: Double = 21.0,
    val description: String? = null,
    val active: Boolean? = true,
    @SerialName("updated_at") val updatedAt: String? = null,
)

@Serializable
data class ApiDocumentItem(
    @SerialName("product_id") val productId: Int? = null,
    val name: String,
    val unit: String? = null,
    val quantity: Double = 1.0,
    @SerialName("unit_price") val unitPrice: Double = 0.0,
    @SerialName("vat_rate") val vatRate: Double = 21.0,
)

@Serializable
data class ApiDocument(
    val id: Int,
    @SerialName("company_id") val companyId: Int? = null,
    @SerialName("client_id") val clientId: Int? = null,
    val type: String,
    val status: String,
    val series: String? = null,
    val number: Int? = null,
    @SerialName("number_full") val numberFull: String? = null,
    @SerialName("issue_date") val issueDate: String? = null,
    @SerialName("due_date") val dueDate: String? = null,
    val currency: String? = null,
    val subtotal: Double? = null,
    @SerialName("vat_total") val vatTotal: Double? = null,
    val total: Double? = null,
    @SerialName("paid_amount") val paidAmount: Double? = null,
    @SerialName("payment_status") val paymentStatus: String? = null,
    val notes: String? = null,
    @SerialName("client_name") val clientName: String? = null,
    @SerialName("client_cui") val clientCui: String? = null,
    @SerialName("client_email") val clientEmail: String? = null,
    @SerialName("efactura_status") val efacturaStatus: String? = null,
    @SerialName("efactura_error") val efacturaError: String? = null,
    val items: List<ApiDocumentItem>? = null,
    @SerialName("updated_at") val updatedAt: String? = null,
)

@Serializable
data class ApiPayment(
    val id: Int,
    @SerialName("company_id") val companyId: Int? = null,
    @SerialName("document_id") val documentId: Int? = null,
    val amount: Double = 0.0,
    val method: String? = null,
    @SerialName("paid_at") val paidAt: String? = null,
    val notes: String? = null,
    @SerialName("updated_at") val updatedAt: String? = null,
)

@Serializable
data class ApiSeries(
    val id: Int,
    val type: String,
    val prefix: String,
    @SerialName("first_number") val firstNumber: Int? = null,
    @SerialName("next_number") val nextNumber: Int? = null,
    val year: Int? = null,
    val active: Boolean? = true,
    @SerialName("is_default") val isDefault: Boolean? = false,
)

@Serializable
data class SyncCompany(
    val id: Int,
    val name: String,
)

@Serializable
data class SyncPullResponse(
    val company: SyncCompany? = null,
    val clients: List<ApiClientDto> = emptyList(),
    val products: List<ApiProduct> = emptyList(),
    val series: List<ApiSeries> = emptyList(),
    val documents: List<ApiDocument> = emptyList(),
    val payments: List<ApiPayment> = emptyList(),
    @SerialName("server_time") val serverTime: String? = null,
    @SerialName("has_more_documents") val hasMoreDocuments: Boolean? = null,
    @SerialName("has_more_payments") val hasMorePayments: Boolean? = null,
)

@Serializable
data class SyncPushOperation(
    @SerialName("op_id") val opId: String,
    val entity: String,
    val action: String,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("server_id") val serverId: Int? = null,
    val payload: Map<String, JsonElement> = emptyMap(),
)

@Serializable
data class SyncPushRequest(
    val operations: List<SyncPushOperation>,
)

@Serializable
data class SyncOpResult(
    @SerialName("op_id") val opId: String,
    val entity: String? = null,
    val ok: Boolean? = null,
    val error: String? = null,
    @SerialName("client_uuid") val clientUuid: String? = null,
    @SerialName("server_id") val serverId: Int? = null,
)

@Serializable
data class SyncPushResponse(
    val results: List<SyncOpResult> = emptyList(),
)

@Serializable
data class DashboardStats(
    @SerialName("clients_receivable_today") val clientsReceivableToday: Double? = null,
    @SerialName("invoices_today_total") val invoicesTodayTotal: Double? = null,
    @SerialName("invoices_month_total") val invoicesMonthTotal: Double = 0.0,
    @SerialName("payments_today_total") val paymentsTodayTotal: Double? = null,
    @SerialName("payments_month_total") val paymentsMonthTotal: Double = 0.0,
    @SerialName("unpaid_count") val unpaidCount: Int? = null,
    @SerialName("drafts_count") val draftsCount: Int? = null,
    @SerialName("payments_today_by_method") val paymentsTodayByMethod: Map<String, Double>? = null,
    @SerialName("payments_month_by_method") val paymentsMonthByMethod: Map<String, Double>? = null,
)

@Serializable
data class DashboardDocRow(
    val id: Int,
    @SerialName("number_full") val numberFull: String? = null,
    @SerialName("client_name") val clientName: String? = null,
    val total: Double? = null,
    val status: String? = null,
    @SerialName("issue_date") val issueDate: String? = null,
)

@Serializable
data class DashboardResponse(
    val stats: DashboardStats? = null,
    val unpaid: List<DashboardDocRow> = emptyList(),
    val drafts: List<DashboardDocRow>? = null,
    @SerialName("recent_documents") val recentDocuments: List<DashboardDocRow>? = null,
    @SerialName("access_label") val accessLabel: String? = null,
)

@Serializable
data class ReportSummary(
    @SerialName("invoices_total") val invoicesTotal: Double? = null,
    @SerialName("payments_total") val paymentsTotal: Double? = null,
    @SerialName("from_date") val fromDate: String? = null,
    @SerialName("to_date") val toDate: String? = null,
)

@Serializable
data class EfacturaStatusResponse(
    val authorized: Boolean? = null,
    val message: String? = null,
    @SerialName("oauth_url") val oauthUrl: String? = null,
)

@Serializable
data class ApiRecurring(
    val id: Int,
    val name: String,
    @SerialName("document_type") val documentType: String? = null,
    val active: Boolean? = null,
    @SerialName("next_run_date") val nextRunDate: String? = null,
    @SerialName("client_name") val clientName: String? = null,
    val total: Double? = null,
)

@Serializable
data class HelpSection(
    val key: String,
    val title: String,
)

@Serializable
data class HelpIndexResponse(
    val sections: List<HelpSection> = emptyList(),
)

@Serializable
data class HelpSectionResponse(
    val title: String,
    val html: String,
)

@Serializable
data class WhatsNewEntry(
    val version: String,
    val title: String? = null,
    val date: String? = null,
    val notes: List<String> = emptyList(),
)

@Serializable
data class WhatsNewResponse(
    val entries: List<WhatsNewEntry> = emptyList(),
)

@Serializable
data class LegalPage(
    val key: String,
    val title: String,
)

@Serializable
data class LegalIndexResponse(
    val pages: List<LegalPage> = emptyList(),
)

@Serializable
data class LegalPageResponse(
    val title: String,
    val html: String,
)

@Serializable
data class AdminStatsData(
    @SerialName("users_count") val usersCount: Int? = null,
    @SerialName("companies_count") val companiesCount: Int? = null,
    @SerialName("documents_count") val documentsCount: Int? = null,
)

@Serializable
data class AdminStatsResponse(
    val data: AdminStatsData? = null,
)

@Serializable
data class AdminCompanyRow(
    val id: Int,
    val name: String,
    val cui: String? = null,
    @SerialName("promo_code") val promoCode: String? = null,
    val email: String? = null,
)

@Serializable
data class AdminCompaniesResponse(
    val companies: List<AdminCompanyRow> = emptyList(),
)

@Serializable
data class CompanyUserRow(
    val id: Int,
    val name: String,
    val email: String,
    val role: String? = null,
    val permissions: List<String>? = null,
)

@Serializable
data class CompanyUsersResponse(
    val users: List<CompanyUserRow> = emptyList(),
)

@Serializable
data class DataEnvelope<T>(
    val data: T,
)

@Serializable
data class ApiErrorBody(
    val message: String? = null,
)

@Serializable
data class WebSessionResponse(
    val url: String,
)

@Serializable
data class AndroidSubscriptionStatus(
    @SerialName("has_access") val hasAccess: Boolean = false,
    @SerialName("access_label") val accessLabel: String? = null,
    @SerialName("in_free_period") val inFreePeriod: Boolean? = null,
    @SerialName("free_until") val freeUntil: String? = null,
)
