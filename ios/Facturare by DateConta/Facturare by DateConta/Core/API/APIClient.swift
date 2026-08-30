import Foundation

enum APIError: LocalizedError, Sendable {
    case invalidURL
    case unauthorized
    case http(Int, String)
    case decoding(String)
    case offline

    var errorDescription: String? {
        switch self {
        case .invalidURL: return "URL invalid."
        case .unauthorized: return "Sesiune expirată. Autentifică-te din nou."
        case .http(_, let message): return message
        case .decoding: return "Răspuns invalid de la server."
        case .offline: return "Fără conexiune la internet."
        }
    }
}

@MainActor
final class APIClient {
    static let shared = APIClient()

    private let session: URLSession
    private let decoder: JSONDecoder
    private let encoder: JSONEncoder

    private var token: String?
    private(set) var companyId: Int?

    private init() {
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 60
        config.waitsForConnectivity = true
        session = URLSession(configuration: config)
        decoder = JSONDecoder()
        encoder = JSONEncoder()
    }

    func setCredentials(token: String?, companyId: Int?) {
        self.token = token
        self.companyId = companyId
    }

    func setCompanyId(_ id: Int?) {
        companyId = id
    }

    func request<T: Decodable>(
        _ method: String,
        path: String,
        body: (any Encodable)? = nil,
        query: [URLQueryItem] = [],
        authorized: Bool = true
    ) async throws -> T {
        let data = try await rawRequest(method, path: path, body: body, query: query, authorized: authorized)
        do {
            return try decoder.decode(T.self, from: data)
        } catch {
            throw APIError.decoding(String(describing: error))
        }
    }

    func rawRequest(
        _ method: String,
        path: String,
        body: (any Encodable)? = nil,
        query: [URLQueryItem] = [],
        authorized: Bool = true
    ) async throws -> Data {
        let trimmed = path.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        let base = APIConfig.baseURL.absoluteString.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        guard var components = URLComponents(string: base + "/" + trimmed) else {
            throw APIError.invalidURL
        }
        if !query.isEmpty {
            components.queryItems = query
            // „+” din timezone ISO8601 (+03:00) trebuie %2B — altfel serverul primește spațiu și crapă Carbon.
            if let encoded = components.percentEncodedQuery?.replacingOccurrences(of: "+", with: "%2B") {
                components.percentEncodedQuery = encoded
            }
        }
        guard let url = components.url else { throw APIError.invalidURL }

        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue(APIConfig.deviceName, forHTTPHeaderField: "X-Client")
        request.setValue(APIConfig.deviceName, forHTTPHeaderField: "X-Device-Name")
        if authorized {
            guard let token else { throw APIError.unauthorized }
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
            if let companyId {
                request.setValue(String(companyId), forHTTPHeaderField: "X-Company-Id")
            }
        }
        if let body {
            request.httpBody = try encoder.encode(AnyEncodable(body))
        }

        let (data, response): (Data, URLResponse)
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.offline
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.http(-1, "Răspuns invalid.")
        }

        if http.statusCode == 401 {
            throw APIError.unauthorized
        }

        guard (200..<300).contains(http.statusCode) else {
            let message = (try? decoder.decode(APIErrorBody.self, from: data))?.message
                ?? String(data: data, encoding: .utf8)
                ?? "Eroare \(http.statusCode)"
            throw APIError.http(http.statusCode, message)
        }

        return data
    }

    func downloadPDF(documentId: Int) async throws -> Data {
        try await rawRequest("GET", path: "documents/\(documentId)/pdf")
    }
}

private struct AnyEncodable: Encodable {
    private let encodeFunc: (Encoder) throws -> Void

    init(_ wrapped: any Encodable) {
        encodeFunc = wrapped.encode
    }

    func encode(to encoder: Encoder) throws {
        try encodeFunc(encoder)
    }
}
