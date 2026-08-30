import SwiftUI

enum AppTheme {
    static let deep = Color(red: 10 / 255, green: 52 / 255, blue: 64 / 255)      // #0a3440
    static let teal = Color(red: 15 / 255, green: 76 / 255, blue: 92 / 255)      // #0f4c5c
    static let accent = Color(red: 15 / 255, green: 118 / 255, blue: 110 / 255)  // #0f766e
    static let warm = Color(red: 196 / 255, green: 92 / 255, blue: 16 / 255)     // #c45c10
    static let mist = Color(red: 240 / 255, green: 246 / 255, blue: 247 / 255)
}

struct SyncStatusBadge: View {
    let status: SyncStatus
    let pending: Int

    var body: some View {
        HStack(spacing: 6) {
            Circle()
                .fill(color)
                .frame(width: 8, height: 8)
            Text(label)
                .font(.caption.weight(.semibold))
                .foregroundStyle(AppTheme.deep.opacity(0.85))
        }
        .padding(.horizontal, 10)
        .padding(.vertical, 5)
        .background(AppTheme.mist, in: Capsule())
    }

    private var label: String {
        if pending > 0 { return "\(pending) în așteptare" }
        return status.label
    }

    private var color: Color {
        if pending > 0 { return AppTheme.warm }
        switch status {
        case .ok, .idle: return .green
        case .syncing: return AppTheme.warm
        case .offline: return .orange
        case .error: return .red
        }
    }
}

/// Avertisment permanent pe ecranul din dreapta: ține app-ul deschis până sync = verde / Sincronizat.
/// La fiecare afișare (remount) alege o culoare aleatoare.
struct SyncKeepOpenBanner: View {
    @State private var tint: Color = .red

    private static let palette: [Color] = [
        .red, .orange, .pink, .purple, .indigo, .blue, .teal, .mint, .green, .cyan, .brown,
    ]

    var body: some View {
        Text("Păstrează aplicația deschisă până când indicatorul de sincronizare (sus, dreapta) este verde și afișează mesajul „Sincronizat”. Nu forța închiderea din lista de aplicații înainte de asta.")
            .font(.subheadline.weight(.bold))
            .foregroundStyle(tint)
            .multilineTextAlignment(.center)
            .fixedSize(horizontal: false, vertical: true)
            .frame(maxWidth: .infinity, alignment: .center)
            .padding(14)
            .background(tint.opacity(0.10), in: RoundedRectangle(cornerRadius: 12, style: .continuous))
            .overlay(
                RoundedRectangle(cornerRadius: 12, style: .continuous)
                    .strokeBorder(tint.opacity(0.40), lineWidth: 1)
            )
            .accessibilityLabel("Avertisment sincronizare: păstrează aplicația deschisă până când indicatorul este verde cu mesajul Sincronizat.")
            .onAppear {
                tint = Self.palette.randomElement() ?? .red
            }
    }
}
