import SwiftUI

struct HelpHubView: View {
    @State private var sections: [ContentSectionRow] = []
    @State private var error: String?
    @State private var loading = true

    var body: some View {
        List {
            if let error {
                Text(error).foregroundStyle(.red)
            }
            Section("Noutăți") {
                NavigationLink {
                    WhatsNewView()
                } label: {
                    Label("Ce este nou…", systemImage: "sparkles")
                }
            }
            Section("Manual") {
                ForEach(sections) { section in
                    NavigationLink {
                        HelpSectionView(key: section.id, title: section.title)
                    } label: {
                        VStack(alignment: .leading, spacing: 2) {
                            Text(section.title)
                            if !section.subtitle.isEmpty {
                                Text(section.subtitle)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Ajutor")
        .overlay { if loading { ProgressView() } }
        .task { await load() }
        .refreshable { await load() }
    }

    private func load() async {
        loading = true
        defer { loading = false }
        do {
            struct Row: Decodable {
                let key: String
                let title: String
                let subtitle: String?
            }
            struct Resp: Decodable { let data: [Row] }
            let response: Resp = try await APIClient.shared.request("GET", path: "help")
            sections = response.data.map {
                ContentSectionRow(id: $0.key, title: $0.title, subtitle: $0.subtitle ?? "")
            }
            error = nil
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct HelpSectionView: View {
    let key: String
    let title: String
    @State private var html: String?
    @State private var error: String?

    var body: some View {
        Group {
            if let html {
                HTMLDocumentView(title: title, html: html)
            } else if let error {
                ContentUnavailableView("Eroare", systemImage: "exclamationmark.triangle", description: Text(error))
            } else {
                ProgressView()
            }
        }
        .task { await load() }
    }

    private func load() async {
        do {
            struct Resp: Decodable {
                let html: String
                let title: String?
            }
            let response: Resp = try await APIClient.shared.request("GET", path: "help/\(key)")
            html = response.html
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct WhatsNewView: View {
    @State private var entries: [WhatsNewEntry] = []
    @State private var currentVersion: String?
    @State private var error: String?

    var body: some View {
        List {
            if let currentVersion {
                Section {
                    Text("Versiune curentă web: \(currentVersion)")
                        .font(.subheadline.weight(.semibold))
                        .foregroundStyle(AppTheme.deep)
                }
            }
            if let error {
                Text(error).foregroundStyle(.red)
            }
            ForEach(entries) { entry in
                Section {
                    ForEach(Array(entry.changes.enumerated()), id: \.offset) { _, change in
                        Text(change)
                    }
                } header: {
                    VStack(alignment: .leading, spacing: 2) {
                        Text("\(entry.version)\(entry.title.map { " — \($0)" } ?? "")")
                        if let date = entry.date {
                            Text(date).font(.caption2)
                        }
                    }
                }
            }
        }
        .navigationTitle("Ce este nou…")
        .task { await load() }
    }

    private func load() async {
        do {
            struct Item: Decodable {
                let version: String
                let date: String?
                let title: String?
                let changes: [String]?
            }
            struct Resp: Decodable {
                let currentVersion: String?
                let data: [Item]
                enum CodingKeys: String, CodingKey {
                    case data
                    case currentVersion = "current_version"
                }
            }
            let response: Resp = try await APIClient.shared.request("GET", path: "help/ce-este-nou")
            currentVersion = response.currentVersion
            entries = response.data.map {
                WhatsNewEntry(
                    version: $0.version,
                    date: $0.date,
                    title: $0.title,
                    changes: $0.changes ?? []
                )
            }
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct LegalHubView: View {
    @State private var pages: [ContentSectionRow] = []
    @State private var error: String?

    var body: some View {
        List {
            if let error {
                Text(error).foregroundStyle(.red)
            }
            Section("Documente") {
                ForEach(pages) { page in
                    NavigationLink {
                        LegalPageView(key: page.id, title: page.title)
                    } label: {
                        VStack(alignment: .leading, spacing: 2) {
                            Text(page.title)
                            if !page.subtitle.isEmpty {
                                Text(page.subtitle)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Legal")
        .task { await load() }
        .refreshable { await load() }
    }

    private func load() async {
        do {
            struct Row: Decodable {
                let key: String
                let title: String
                let subtitle: String?
            }
            struct Resp: Decodable { let data: [Row] }
            let response: Resp = try await APIClient.shared.request(
                "GET", path: "legal", authorized: false
            )
            pages = response.data.map {
                ContentSectionRow(id: $0.key, title: $0.title, subtitle: $0.subtitle ?? "")
            }
            error = nil
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct LegalPageView: View {
    let key: String
    let title: String
    @State private var html: String?
    @State private var error: String?

    var body: some View {
        Group {
            if let html {
                HTMLDocumentView(title: title, html: html)
            } else if let error {
                ContentUnavailableView("Eroare", systemImage: "exclamationmark.triangle", description: Text(error))
            } else {
                ProgressView()
            }
        }
        .task { await load() }
    }

    private func load() async {
        do {
            struct Resp: Decodable { let html: String }
            let response: Resp = try await APIClient.shared.request(
                "GET", path: "legal/\(key)", authorized: false
            )
            html = response.html
        } catch {
            self.error = error.localizedDescription
        }
    }
}
