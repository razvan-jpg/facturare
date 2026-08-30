import Foundation

enum AppVersion {
    /// Versiunea marketing a app-ului iOS (CFBundleShortVersionString).
    static var ios: String {
        let short = Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String
        let build = Bundle.main.infoDictionary?["CFBundleVersion"] as? String
        if let short, let build, short != build {
            return "\(short) (\(build))"
        }
        return short ?? build ?? "—"
    }
}
