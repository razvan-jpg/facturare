import Foundation
import UIKit

/// Deschide Safari autentificat via one-time token (POST /api/v1/web-session).
enum WebSession {
    @MainActor
    static func open(path: String) async {
        let redirect = path.hasPrefix("/") ? path : "/\(path)"
        do {
            struct Body: Encodable { let redirect: String }
            struct Response: Decodable { let url: String }
            let response: Response = try await APIClient.shared.request(
                "POST",
                path: "web-session",
                body: Body(redirect: redirect)
            )
            guard let url = URL(string: response.url) else { return }
            await UIApplication.shared.open(url)
        } catch {
            // Fallback: deschide totuși path-ul (utilizatorul se poate loga manual).
            let fallback = APIConfig.webBaseURL.appending(path: redirect.trimmingCharacters(in: CharacterSet(charactersIn: "/")))
            await UIApplication.shared.open(fallback)
        }
    }

    @MainActor
    static func openURLString(_ absoluteOrPath: String) async {
        if absoluteOrPath.hasPrefix("http://") || absoluteOrPath.hasPrefix("https://") {
            guard let url = URL(string: absoluteOrPath) else { return }
            let path = url.path.isEmpty ? "/dashboard" : url.path
            let query = url.query.map { "?\($0)" } ?? ""
            await open(path: path + query)
            return
        }
        await open(path: absoluteOrPath)
    }
}
