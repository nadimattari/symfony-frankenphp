/**
 * Shared API client for the demo services. All URLs are local Symfony
 * endpoints; no Lamiral-domain or primefaces.org URLs are copied.
 */

import type { ApiCollection } from "../types/api";
import { FetchClient, type QueryValue } from "../utils/fetch";

export function createApiClient(): FetchClient {
  const baseUrl =
    typeof window !== "undefined" && window.API_BASE_URL
      ? window.API_BASE_URL
      : "/api";

  // Endpoints are relative to baseUrl so `/api/login` is requested exactly
  // once, whether the base URL comes from window.API_BASE_URL or the default.
  return new FetchClient({
    baseUrl,
    endpoints: {
      login: "/login",
      refresh: "/token/refresh",
      logout: "/logout",
    },
  });
}

export const apiClient = createApiClient();

/** Normalise `{ data }`, Hydra collections and bare arrays to a typed array. */
export function collectionData<T>(payload: ApiCollection<T> | T[] | null): T[] {
  if (Array.isArray(payload)) {
    return payload;
  }
  if (!payload) {
    return [];
  }

  const items = payload.data ?? payload["hydra:member"] ?? [];
  return items as T[];
}

export class ApiService<T extends object> {
  constructor(
    protected readonly client: FetchClient,
    protected readonly path: string,
  ) {}

  protected listFrom(
    path: string,
    query?: Record<string, QueryValue>,
  ): Promise<T[]> {
    return this.client
      .get<ApiCollection<T> | T[]>(path, { query })
      .then(collectionData);
  }

  protected list(query?: Record<string, QueryValue>): Promise<T[]> {
    return this.listFrom(this.path, query);
  }

  get(id: number | string): Promise<T | null> {
    return this.client.get<T>(`${this.path}/${id}`);
  }

  create(data: Partial<T>): Promise<T> {
    return this.client.post<T>(this.path, data);
  }

  update(id: number | string, data: Partial<T>): Promise<T> {
    return this.client.patch<T>(`${this.path}/${id}`, data);
  }

  remove(id: number | string): Promise<null> {
    return this.client.delete<null>(`${this.path}/${id}`);
  }
}
