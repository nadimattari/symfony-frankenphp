/**
 * Typed auth API service. All methods go through the shared FetchClient, so
 * bearer tokens, CSRF headers and single-flight 401 refresh are inherited
 * without any auth-specific fetch code here.
 */
import type { AuthResponse, LoginRequest } from "../types/api";
import { apiClient } from "./api";

/** Minimal surface the service needs; kept structural for easy injection. */
export interface AuthApi {
  post<T>(path: string, body?: unknown): Promise<T>;
}

export class AuthService {
  constructor(private readonly client: AuthApi = apiClient) {}

  login(credentials: LoginRequest): Promise<AuthResponse> {
    return this.client.post<AuthResponse>("/login", credentials);
  }

  logout(): Promise<void> {
    return this.client.post<void>("/logout");
  }
}

export const authService = new AuthService();
