/**
 * Typed, framework-agnostic fetch wrapper ported from the Lamiral Tier A
 * pattern. Auth storage, routing and endpoints are injected by the caller so
 * this module can be reused and tested without Pinia or vue-router.
 */

import type { ApiErrorBody, AuthResponse, JwtPayload } from "../types/api";

export interface ApiEndpoints {
  login: string;
  refresh: string;
  logout: string;
}

export type QueryValue = string | number | boolean | null | undefined;

export interface RequestOptions {
  headers?: Record<string, string>;
  query?: Record<string, QueryValue>;
}

export interface FetchClientOptions {
  baseUrl?: string;
  endpoints?: Partial<ApiEndpoints>;
  csrfCookieName?: string;
  credentials?: RequestCredentials;
  defaultHeaders?: Record<string, string>;
  fetchImpl?: typeof fetch;
  getToken?: () => string | null;
  setToken?: (token: string | null) => void;
  onRefreshed?: (payload: AuthResponse) => void;
  onUnauthorized?: () => void;
}

/** Late-bound auth callbacks, supplied by the Pinia store at bootstrap. */
export interface AuthHooks {
  getToken?: () => string | null;
  setToken?: (token: string | null) => void;
  onRefreshed?: (payload: AuthResponse) => void;
  onUnauthorized?: () => void;
}

const DEFAULT_ENDPOINTS: ApiEndpoints = {
  login: "/api/login",
  refresh: "/api/token/refresh",
  logout: "/api/logout",
};

const BODY_METHODS = new Set(["POST", "PUT", "PATCH"]);
const CSRF_METHODS = new Set(["POST", "PUT", "PATCH", "DELETE"]);

export class ApiError extends Error {
  readonly status: number;
  readonly body: unknown;

  constructor(message: string, status: number, body?: unknown) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.body = body;
  }
}

export function readCookie(name: string): string | null {
  if (typeof document === "undefined") {
    return null;
  }

  const prefix = `${name}=`;
  for (const part of document.cookie.split(";")) {
    const value = part.trim();
    if (value.startsWith(prefix)) {
      return decodeURIComponent(value.slice(prefix.length));
    }
  }

  return null;
}

export function filenameFromContentDisposition(header: string | null): string {
  if (!header) {
    return "download";
  }

  const utf8 = header.match(/filename\*=UTF-8''([^;]+)/i);
  if (utf8?.[1]) {
    return decodeURIComponent(utf8[1]);
  }

  const plain = header.match(/filename="?([^";]+)"?/i);
  return plain?.[1]?.trim() || "download";
}

export function saveBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

export class FetchClient {
  private readonly baseUrl: string;
  private readonly endpoints: ApiEndpoints;
  private readonly csrfCookieName: string;
  private readonly credentials: RequestCredentials;
  private readonly defaultHeaders: Record<string, string>;
  private readonly fetchImpl: typeof fetch;
  private getToken: () => string | null;
  private setToken: (token: string | null) => void;
  private onRefreshed: (payload: AuthResponse) => void;
  private onUnauthorized: () => void;
  private refreshInFlight: Promise<boolean> | null = null;

  constructor(options: FetchClientOptions = {}) {
    this.baseUrl = (options.baseUrl ?? "").replace(/\/$/, "");
    this.endpoints = { ...DEFAULT_ENDPOINTS, ...options.endpoints };
    this.csrfCookieName = options.csrfCookieName ?? "csrf";
    this.credentials = options.credentials ?? "same-origin";
    this.defaultHeaders = options.defaultHeaders ?? {};
    this.fetchImpl =
      options.fetchImpl ?? ((input, init) => globalThis.fetch(input, init));
    this.getToken = options.getToken ?? (() => null);
    this.setToken = options.setToken ?? (() => undefined);
    this.onRefreshed = options.onRefreshed ?? (() => undefined);
    this.onUnauthorized = options.onUnauthorized ?? (() => undefined);
  }

  /** Wire auth storage/callbacks after construction (e.g. once Pinia exists). */
  setAuthHooks(hooks: AuthHooks): void {
    if (hooks.getToken) {
      this.getToken = hooks.getToken;
    }
    if (hooks.setToken) {
      this.setToken = hooks.setToken;
    }
    if (hooks.onRefreshed) {
      this.onRefreshed = hooks.onRefreshed;
    }
    if (hooks.onUnauthorized) {
      this.onUnauthorized = hooks.onUnauthorized;
    }
  }

  get<T>(path: string, options?: RequestOptions): Promise<T> {
    return this.request<T>("GET", path, options);
  }

  post<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return this.request<T>("POST", path, { ...options, body });
  }

  put<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return this.request<T>("PUT", path, { ...options, body });
  }

  patch<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return this.request<T>("PATCH", path, { ...options, body });
  }

  delete<T>(path: string, options?: RequestOptions): Promise<T> {
    return this.request<T>("DELETE", path, options);
  }

  download(path: string, options?: RequestOptions): Promise<Blob> {
    return this.request<Blob>("GET", path, options, true);
  }

  /** Decode a JWT payload without verifying it — the server owns verification. */
  isTokenExpired(token: string | null, skewSeconds = 60): boolean {
    if (typeof token !== "string") {
      return true;
    }

    const parts = token.split(".");
    if (parts.length !== 3) {
      return true;
    }

    try {
      const payload = JSON.parse(
        atob(parts[1].replace(/-/g, "+").replace(/_/g, "/")),
      ) as JwtPayload;
      if (typeof payload?.exp !== "number") {
        return false;
      }
      return payload.exp - Math.floor(Date.now() / 1000) <= skewSeconds;
    } catch {
      return true;
    }
  }

  /** Concurrent callers share a single refresh so rotating tokens are never spent twice. */
  refresh(): Promise<boolean> {
    if (!this.refreshInFlight) {
      this.refreshInFlight = this.performRefresh().finally(() => {
        this.refreshInFlight = null;
      });
    }
    return this.refreshInFlight;
  }

  private resolveUrl(path: string): string {
    if (/^[a-z][a-z\d+.-]*:/i.test(path) || path.startsWith("//")) {
      return path;
    }
    return `${this.baseUrl}/${path.replace(/^\//, "")}`;
  }

  private isAuthEndpoint(path: string): boolean {
    const resolved = this.resolveUrl(path).split("?")[0];
    return Object.values(this.endpoints).some(
      (endpoint) => this.resolveUrl(endpoint).split("?")[0] === resolved,
    );
  }

  private async request<T>(
    method: string,
    path: string,
    options: RequestOptions & { body?: unknown } = {},
    asBlob = false,
  ): Promise<T> {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(options.query ?? {})) {
      if (value !== null && value !== undefined) {
        query.append(key, String(value));
      }
    }
    const suffix = query.toString();
    const url = this.resolveUrl(path) + (suffix ? `?${suffix}` : "");
    return this.perform<T>(
      method,
      url,
      options,
      false,
      this.isAuthEndpoint(path),
      asBlob,
    );
  }

  private async perform<T>(
    method: string,
    url: string,
    options: RequestOptions & { body?: unknown },
    retried: boolean,
    isAuthEndpoint: boolean,
    asBlob: boolean,
  ): Promise<T> {
    const token = this.getToken();

    if (!retried && !isAuthEndpoint && token && this.isTokenExpired(token)) {
      await this.refresh();
    }

    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...this.defaultHeaders,
      ...options.headers,
    };

    if (method === "PATCH" && !options.headers?.["Content-Type"]) {
      headers["Content-Type"] = "application/merge-patch+json";
    }

    const csrf = readCookie(this.csrfCookieName);
    if (csrf && (CSRF_METHODS.has(method) || this.isAuthEndpoint(url))) {
      headers["X-CSRF-Token"] = csrf;
    }

    if (token && !isAuthEndpoint) {
      headers.Authorization = `Bearer ${token}`;
    }

    const init: RequestInit = {
      method,
      mode: "cors",
      cache: "no-cache",
      credentials: this.credentials,
      headers,
      redirect: "follow",
      referrerPolicy: "no-referrer",
    };

    if (BODY_METHODS.has(method) && options.body !== undefined) {
      init.body = JSON.stringify(options.body);
    }

    const response = await this.fetchImpl(url, init);

    if (response.status === 401 && !retried && !isAuthEndpoint) {
      if (await this.refresh()) {
        return this.perform<T>(
          method,
          url,
          options,
          true,
          isAuthEndpoint,
          asBlob,
        );
      }
      this.setToken(null);
      this.onUnauthorized();
    }

    if (!response.ok) {
      const body = await this.extractErrorBody(response);
      throw new ApiError(
        this.errorMessage(body, response.status),
        response.status,
        body,
      );
    }

    if (asBlob) {
      return (await response.blob()) as T;
    }
    if (response.status === 204) {
      return null as T;
    }

    const text = await response.text();
    return (text ? JSON.parse(text) : null) as T;
  }

  private async performRefresh(): Promise<boolean> {
    const csrf = readCookie(this.csrfCookieName);
    if (!csrf) {
      return false;
    }

    try {
      const response = await this.fetchImpl(
        this.resolveUrl(this.endpoints.refresh),
        {
          method: "POST",
          credentials: this.credentials,
          cache: "no-cache",
          headers: {
            Accept: "application/json",
            "X-CSRF-Token": csrf,
          },
        },
      );

      if (!response.ok) {
        return false;
      }

      const payload = (await response.json()) as AuthResponse;
      if (!payload?.token) {
        return false;
      }

      this.setToken(payload.token);
      this.onRefreshed(payload);
      return true;
    } catch {
      return false;
    }
  }

  private async extractErrorBody(
    response: Response,
  ): Promise<ApiErrorBody | string> {
    const text = await response.text();
    if (!text) {
      return `Request failed with status ${response.status}`;
    }

    try {
      return JSON.parse(text) as ApiErrorBody;
    } catch {
      return text;
    }
  }

  private errorMessage(body: ApiErrorBody | string, status: number): string {
    if (typeof body === "string") {
      return body;
    }

    const first = body.errors ? Object.values(body.errors)[0] : undefined;
    const detail = Array.isArray(first) ? first[0] : first;
    return (
      detail ||
      body.detail ||
      body["hydra:description"] ||
      body.message ||
      body.error ||
      `Request failed with status ${status}`
    );
  }
}
