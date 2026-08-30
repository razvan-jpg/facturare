import SwiftUI

private enum AuthLayout {
    static let cardMaxWidth: CGFloat = 400
}

struct LoginView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(\.verticalSizeClass) private var verticalSizeClass
    @Environment(\.horizontalSizeClass) private var horizontalSizeClass
    @State private var email = ""
    @State private var password = ""
    @State private var showRegister = false

    private var isLandscapePhone: Bool {
        verticalSizeClass == .compact && horizontalSizeClass == .compact
    }

    var body: some View {
        NavigationStack {
            ZStack {
                LinearGradient(
                    colors: [AppTheme.deep, AppTheme.teal, AppTheme.accent.opacity(0.9)],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                )
                .ignoresSafeArea()

                RadialGradient(
                    colors: [.white.opacity(0.12), .clear],
                    center: .top,
                    startRadius: 20,
                    endRadius: 420
                )
                .ignoresSafeArea()

                ScrollView {
                    Group {
                        if isLandscapePhone {
                            HStack(alignment: .center, spacing: 28) {
                                brandHeader
                                    .frame(maxWidth: .infinity)
                                loginCard
                                    .frame(maxWidth: AuthLayout.cardMaxWidth)
                            }
                            .padding(.horizontal, 28)
                            .padding(.vertical, 20)
                        } else {
                            VStack(spacing: 28) {
                                brandHeader
                                loginCard
                            }
                            .frame(maxWidth: AuthLayout.cardMaxWidth)
                            .frame(maxWidth: .infinity)
                            .padding(.horizontal, 24)
                            .padding(.vertical, horizontalSizeClass == .regular ? 56 : 40)
                        }
                    }
                    .frame(maxWidth: .infinity)
                }
                .scrollDismissesKeyboard(.interactively)
            }
            .sheet(isPresented: $showRegister) {
                RegisterView()
                    .presentationDetents(horizontalSizeClass == .regular ? [.large] : [.medium, .large])
                    .presentationDragIndicator(.visible)
            }
        }
    }

    private var brandHeader: some View {
        VStack(spacing: isLandscapePhone ? 6 : 10) {
            Text("DateConta")
                .font(.system(size: isLandscapePhone ? 30 : 36, weight: .bold, design: .rounded))
                .foregroundStyle(.white)
            Text("Facturare")
                .font(.title3.weight(.semibold))
                .foregroundStyle(.white.opacity(0.9))
            Text("Sincronizat cu factura.dateconta.ro")
                .font(.subheadline)
                .foregroundStyle(.white.opacity(0.72))
                .multilineTextAlignment(.center)
        }
        .padding(.top, isLandscapePhone ? 0 : 12)
    }

    private var loginCard: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Autentificare")
                .font(.headline)
                .foregroundStyle(AppTheme.deep)

            authField(title: "Email", text: $email, isSecure: false)
                .textContentType(.username)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()

            authField(title: "Parolă", text: $password, isSecure: true)
                .textContentType(.password)

            if let error = auth.errorMessage {
                Text(error)
                    .font(.footnote)
                    .foregroundStyle(.red)
                    .fixedSize(horizontal: false, vertical: true)
            }

            Button {
                Task {
                    await auth.login(
                        email: email.trimmingCharacters(in: .whitespacesAndNewlines),
                        password: password
                    )
                }
            } label: {
                Group {
                    if auth.isLoading {
                        ProgressView().tint(.white)
                    } else {
                        Text("Intră în cont").fontWeight(.semibold)
                    }
                }
                .frame(maxWidth: .infinity)
                .padding(.vertical, 13)
                .background(AppTheme.warm, in: RoundedRectangle(cornerRadius: 12, style: .continuous))
                .foregroundStyle(.white)
            }
            .disabled(auth.isLoading || email.isEmpty || password.isEmpty)
            .padding(.top, 4)

            HStack {
                Button("Cont nou") { showRegister = true }
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(AppTheme.teal)

                Spacer()

                Text("Demo: demo@dateconta.ro")
                    .font(.caption2)
                    .foregroundStyle(AppTheme.deep.opacity(0.45))
                    .lineLimit(1)
                    .minimumScaleFactor(0.8)
            }
            .padding(.top, 2)
        }
        .padding(22)
        .background(
            RoundedRectangle(cornerRadius: 20, style: .continuous)
                .fill(.white)
                .shadow(color: .black.opacity(0.18), radius: 24, y: 12)
        )
    }

    @ViewBuilder
    private func authField(title: String, text: Binding<String>, isSecure: Bool) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption.weight(.semibold))
                .foregroundStyle(AppTheme.deep.opacity(0.65))
            Group {
                if isSecure {
                    SecureField("", text: text)
                } else {
                    TextField("", text: text)
                }
            }
            .padding(.horizontal, 12)
            .padding(.vertical, 11)
            .background(AppTheme.mist, in: RoundedRectangle(cornerRadius: 10, style: .continuous))
        }
    }
}

struct RegisterView: View {
    @Environment(AuthStore.self) private var auth
    @Environment(\.dismiss) private var dismiss
    @State private var name = ""
    @State private var email = ""
    @State private var password = ""
    @State private var confirm = ""

    var body: some View {
        NavigationStack {
            ZStack {
                AppTheme.mist.ignoresSafeArea()

                ScrollView {
                    VStack(alignment: .leading, spacing: 16) {
                        Text("Creează un cont nou")
                            .font(.title3.bold())
                            .foregroundStyle(AppTheme.deep)

                        Text("Contul e același ca pe web — poți continua pe calculator oricând.")
                            .font(.subheadline)
                            .foregroundStyle(AppTheme.deep.opacity(0.65))

                        field(title: "Nume", text: $name, secure: false)
                        field(title: "Email", text: $email, secure: false)
                            .textInputAutocapitalization(.never)
                            .keyboardType(.emailAddress)
                            .autocorrectionDisabled()
                        field(title: "Parolă", text: $password, secure: true)
                        field(title: "Confirmă parola", text: $confirm, secure: true)

                        if let error = auth.errorMessage {
                            Text(error)
                                .font(.footnote)
                                .foregroundStyle(.red)
                        }

                        Button {
                            Task {
                                await auth.register(
                                    name: name,
                                    email: email.trimmingCharacters(in: .whitespacesAndNewlines),
                                    password: password,
                                    passwordConfirmation: confirm
                                )
                                if auth.isAuthenticated { dismiss() }
                            }
                        } label: {
                            Group {
                                if auth.isLoading {
                                    ProgressView().tint(.white)
                                } else {
                                    Text("Creează cont").fontWeight(.semibold)
                                }
                            }
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 13)
                            .background(AppTheme.accent, in: RoundedRectangle(cornerRadius: 12, style: .continuous))
                            .foregroundStyle(.white)
                        }
                        .disabled(auth.isLoading || name.isEmpty || email.isEmpty || password.count < 8)
                    }
                    .padding(22)
                    .frame(maxWidth: AuthLayout.cardMaxWidth)
                    .background(
                        RoundedRectangle(cornerRadius: 20, style: .continuous)
                            .fill(.white)
                            .shadow(color: .black.opacity(0.08), radius: 16, y: 6)
                    )
                    .frame(maxWidth: .infinity)
                    .padding(24)
                }
                .scrollDismissesKeyboard(.interactively)
            }
            .navigationTitle("Înregistrare")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Închide") { dismiss() }
                }
            }
        }
    }

    @ViewBuilder
    private func field(title: String, text: Binding<String>, secure: Bool) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption.weight(.semibold))
                .foregroundStyle(AppTheme.deep.opacity(0.65))
            Group {
                if secure {
                    SecureField("", text: text)
                } else {
                    TextField("", text: text)
                }
            }
            .padding(.horizontal, 12)
            .padding(.vertical, 11)
            .background(AppTheme.mist, in: RoundedRectangle(cornerRadius: 10, style: .continuous))
        }
    }
}
