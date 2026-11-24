/**
 * Shared API contracts. Keep this module type-only: the fetch wrapper is
 * compiled independently for its focused node:test suite.
 */

export type Role = `ROLE_${string}`;

export interface JwtPayload {
  sub?: string;
  exp?: number;
  iat?: number;
  roles?: Role[];
}

export interface LoginRequest {
  username: string;
  password: string;
}

export interface AuthIdentity {
  username: string;
  name?: string;
  roles: Role[];
}

/** Shape returned by /api/login and /api/token/refresh. */
export interface AuthResponse {
  token: string;
  data?: Partial<AuthIdentity>;
}

export interface ApiErrorBody {
  detail?: string;
  message?: string;
  error?: string;
  "hydra:description"?: string;
  errors?: Record<string, string | string[]>;
}

/** Supports both the plain `{ data, total }` demo shape and API Platform's Hydra collections. */
export interface ApiCollection<T> {
  data?: T[];
  total?: number;
  "hydra:member"?: T[];
  "hydra:totalItems"?: number;
}

export interface Country {
  name: string;
  code: string;
}

export interface Product {
  id?: number | string;
  code?: string;
  name?: string;
  description?: string;
  image?: string;
  price?: number;
  category?: string;
  quantity?: number;
  inventoryStatus?: string;
  rating?: number;
  status?: string;
}

export interface Customer {
  id?: number | string;
  name?: string;
  company?: string;
  country?: Country;
  date?: string;
  status?: string;
  activity?: number;
  representative?: { name: string; image: string };
  balance?: number;
}

export interface Photo {
  itemImageSrc?: string;
  thumbnailImageSrc?: string;
  alt?: string;
  title?: string;
}
