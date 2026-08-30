package ro.dateconta.facturare.ui.shell

enum class AppSection(val title: String) {
    Home("Acasă"),
    Emite("Emite"),
    Liste("Liste"),
    Catalog("Catalog"),
    Reports("Rapoarte"),
    Help("Ajutor"),
    Legal("Legal"),
    Settings("Setări"),
    Admin("Admin"),
    ;

    companion object {
        val primaryTabs = listOf(Home, Emite, Liste, Catalog)
        val moreSections = listOf(Reports, Help, Legal, Settings, Admin)
    }
}
