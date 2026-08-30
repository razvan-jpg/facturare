package ro.dateconta.facturare.ui.shell

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.List
import androidx.compose.material.icons.filled.AddBox
import androidx.compose.material.icons.filled.GridView
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.MoreHoriz
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.material3.ExperimentalMaterial3Api
import kotlinx.coroutines.launch
import ro.dateconta.facturare.AppState
import ro.dateconta.facturare.BuildConfig
import ro.dateconta.facturare.ui.admin.AdminScreen
import ro.dateconta.facturare.ui.catalog.CatalogScreen
import ro.dateconta.facturare.ui.components.SyncStatusBadge
import ro.dateconta.facturare.ui.dashboard.DashboardScreen
import ro.dateconta.facturare.ui.emite.EmiteScreen
import ro.dateconta.facturare.ui.help.HelpScreen
import ro.dateconta.facturare.ui.legal.LegalScreen
import ro.dateconta.facturare.ui.liste.ListeScreen
import ro.dateconta.facturare.ui.reports.ReportsScreen
import ro.dateconta.facturare.ui.settings.SettingsScreen
import ro.dateconta.facturare.ui.theme.AppTheme

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RootShellScreen(appState: AppState) {
  var section by remember { mutableStateOf(AppSection.Home) }
  var companyMenuExpanded by remember { mutableStateOf(false) }
  var companyEpoch by remember { mutableIntStateOf(0) }
  val scope = rememberCoroutineScope()
  val screenWidthDp = LocalConfiguration.current.screenWidthDp
  val isWide = screenWidthDp >= 600

  LaunchedEffect(Unit) {
    appState.auth.refreshMe()
    if (appState.auth.currentCompanyId != null) {
      appState.syncNow()
    }
  }

  if (appState.auth.companies.isEmpty() && !appState.auth.isAdmin) {
    SettingsScreen(appState, forceCreateCompany = true)
    return
  }

  if (isWide) {
    Row(Modifier.fillMaxSize()) {
      Sidebar(
        appState = appState,
        section = section,
        onSection = { section = it; companyEpoch++ },
        companyMenuExpanded = companyMenuExpanded,
        onCompanyMenu = { companyMenuExpanded = it },
        onLogout = { scope.launch { appState.logout() } },
        modifier = Modifier.width(280.dp).fillMaxHeight(),
      )
      Column(Modifier.fillMaxSize()) {
        SyncKeepOpenBanner()
        SectionContent(appState, section, companyEpoch, Modifier.weight(1f))
      }
    }
  } else {
    var tabIndex by remember { mutableIntStateOf(0) }
    var showMore by remember { mutableStateOf(false) }

    Scaffold(
      topBar = {
        TopAppBar(
          title = {
            Text(
              appState.auth.currentCompany?.name ?: "Facturare",
              maxLines = 1,
            )
          },
          actions = {
            SyncStatusBadge(
              status = appState.syncStatus,
              pending = appState.pendingCount,
              modifier = Modifier
                .padding(end = 8.dp)
                .clickable { appState.syncNow() },
            )
          },
        )
      },
      bottomBar = {
        NavigationBar {
          AppSection.primaryTabs.forEachIndexed { index, tab ->
            NavigationBarItem(
              selected = tabIndex == index && !showMore,
              onClick = {
                tabIndex = index
                showMore = false
                section = tab
              },
              icon = { Icon(tab.icon(), contentDescription = tab.title) },
              label = { Text(tab.title) },
            )
          }
          NavigationBarItem(
            selected = showMore,
            onClick = { showMore = true },
            icon = { Icon(Icons.Default.MoreHoriz, contentDescription = "Mai mult") },
            label = { Text("Mai mult") },
          )
        }
      },
    ) { padding ->
      if (showMore) {
        MoreMenu(
          appState = appState,
          onSelect = { section = it; showMore = false },
          modifier = Modifier.padding(padding),
        )
      } else {
        SectionContent(
          appState,
          section,
          companyEpoch,
          Modifier.padding(padding),
        )
      }
    }
  }
}

@Composable
private fun SectionContent(
  appState: AppState,
  section: AppSection,
  companyEpoch: Int,
  modifier: Modifier = Modifier,
) {
  Box(modifier.fillMaxSize().padding(horizontal = 16.dp)) {
    when (section) {
      AppSection.Home -> DashboardScreen(appState, key = companyEpoch)
      AppSection.Emite -> EmiteScreen(appState)
      AppSection.Liste -> ListeScreen(appState)
      AppSection.Catalog -> CatalogScreen(appState)
      AppSection.Reports -> ReportsScreen(appState)
      AppSection.Help -> HelpScreen(appState)
      AppSection.Legal -> LegalScreen(appState)
      AppSection.Settings -> SettingsScreen(appState)
      AppSection.Admin -> AdminScreen(appState)
    }
  }
}

@Composable
private fun Sidebar(
  appState: AppState,
  section: AppSection,
  onSection: (AppSection) -> Unit,
  companyMenuExpanded: Boolean,
  onCompanyMenu: (Boolean) -> Unit,
  onLogout: () -> Unit,
  modifier: Modifier = Modifier,
) {
  val scope = rememberCoroutineScope()
  Column(
    modifier
      .background(AppTheme.Mist)
      .verticalScroll(rememberScrollState())
      .padding(16.dp),
  ) {
    Text("DateConta", fontWeight = FontWeight.Bold, fontSize = 22.sp, color = AppTheme.Deep)
    Text("FACTURARE", fontSize = 12.sp, color = AppTheme.Teal)
    Spacer(Modifier.height(16.dp))

    Box {
      TextButton(onClick = { onCompanyMenu(true) }) {
        Text(appState.auth.currentCompany?.name ?: "Alege firmă")
      }
      DropdownMenu(expanded = companyMenuExpanded, onDismissRequest = { onCompanyMenu(false) }) {
        appState.auth.companies.forEach { company ->
          DropdownMenuItem(
            text = { Text(company.name) },
            onClick = {
              onCompanyMenu(false)
              scope.launch {
                appState.auth.switchCompany(company)
                appState.syncNow()
                onSection(section)
              }
            },
          )
        }
      }
    }

    HorizontalDivider(Modifier.padding(vertical = 8.dp))

    (AppSection.primaryTabs + AppSection.moreSections).forEach { item ->
      if (item == AppSection.Admin && !appState.auth.isAdmin) return@forEach
      val selected = section == item
      TextButton(
        onClick = { onSection(item) },
        modifier = Modifier.fillMaxWidth(),
      ) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.Start) {
          Icon(item.icon(), contentDescription = null, tint = if (selected) AppTheme.Accent else AppTheme.Deep)
          Spacer(Modifier.width(8.dp))
          Text(item.title, color = if (selected) AppTheme.Accent else AppTheme.Deep, fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal)
        }
      }
    }

    Spacer(Modifier.height(16.dp))
    TextButton(onClick = onLogout) { Text("Deconectare") }
    Text(
      "Android ${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})",
      fontSize = 11.sp,
      color = AppTheme.Deep.copy(alpha = 0.6f),
    )
    appState.auth.webAppVersion?.let {
      Text("Web $it", fontSize = 11.sp, color = AppTheme.Deep.copy(alpha = 0.6f))
    }
  }
}

@Composable
private fun MoreMenu(appState: AppState, onSelect: (AppSection) -> Unit, modifier: Modifier = Modifier) {
  Column(modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
    AppSection.moreSections.forEach { item ->
      if (item == AppSection.Admin && !appState.auth.isAdmin) return@forEach
      TextButton(onClick = { onSelect(item) }, modifier = Modifier.fillMaxWidth()) {
        Text(item.title, modifier = Modifier.fillMaxWidth())
      }
    }
    Text(
      "Android ${BuildConfig.VERSION_NAME} · Web ${appState.auth.webAppVersion ?: "—"}",
      fontSize = 12.sp,
      color = AppTheme.Deep.copy(alpha = 0.6f),
      modifier = Modifier.padding(top = 16.dp),
    )
  }
}

@Composable
private fun SyncKeepOpenBanner() {
  Text(
    "Păstrează aplicația deschisă până când indicatorul de sincronizare este verde și afișează „Sincronizat”.",
    modifier = Modifier
      .fillMaxWidth()
      .padding(16.dp)
      .background(AppTheme.Warm.copy(alpha = 0.1f))
      .padding(12.dp),
    color = AppTheme.Warm,
    fontWeight = FontWeight.Bold,
  )
}

private fun AppSection.icon(): ImageVector = when (this) {
  AppSection.Home -> Icons.Default.Home
  AppSection.Emite -> Icons.Default.AddBox
  AppSection.Liste -> Icons.AutoMirrored.Filled.List
  AppSection.Catalog -> Icons.Default.GridView
  else -> Icons.Default.MoreHoriz
}
