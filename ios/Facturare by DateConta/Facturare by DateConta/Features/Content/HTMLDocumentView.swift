import SwiftUI
import WebKit

struct HTMLDocumentView: View {
    let title: String
    let html: String

    var body: some View {
        HTMLWebView(html: html)
            .navigationTitle(title)
            .navigationBarTitleDisplayMode(.inline)
    }
}

private struct HTMLWebView: UIViewRepresentable {
    let html: String

    func makeUIView(context: Context) -> WKWebView {
        let config = WKWebViewConfiguration()
        let view = WKWebView(frame: .zero, configuration: config)
        view.isOpaque = false
        view.backgroundColor = .systemBackground
        view.scrollView.backgroundColor = .systemBackground
        return view
    }

    func updateUIView(_ webView: WKWebView, context: Context) {
        let wrapped = """
        <!DOCTYPE html>
        <html><head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 16px;
                 line-height: 1.5; color: #0a3440; padding: 12px 16px; margin: 0; }
          img { max-width: 100%; height: auto; }
          a { color: #0f766e; }
          h1,h2,h3 { color: #0a3440; }
          .help-lead, .help-meta-line { color: #627d98; }
        </style>
        </head><body>\(html)</body></html>
        """
        webView.loadHTMLString(wrapped, baseURL: APIConfig.webBaseURL)
    }
}

struct ContentSectionRow: Identifiable, Hashable {
    let id: String
    let title: String
    let subtitle: String
}

struct WhatsNewEntry: Identifiable {
    var id: String { version }
    let version: String
    let date: String?
    let title: String?
    let changes: [String]
}
