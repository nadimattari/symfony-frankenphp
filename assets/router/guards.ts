/**
 * Framework-neutral navigation guard logic. The router passes Vue Router's
 * `to` record here; keeping the decision pure makes the auth rules testable
 * without mounting a router or a Pinia store.
 */
import type { Role } from "../types/api";

export interface AuthState {
  token: string | null;
  isAuthorisedFor(role: Role): boolean;
}

export interface AuthRouteMeta {
  requiresAuth?: boolean;
  requiresRole?: Role;
  public?: boolean;
}

export interface AuthRouteLocation {
  path: string;
  fullPath: string;
  meta: AuthRouteMeta;
}

export type AuthGuardResult =
  | true
  | { name: string; query?: Record<string, string> };

export const PUBLIC_PAGES = new Set([
  "/",
  "/auth/login",
  "/auth/access",
  "/auth/error",
  "/landing",
  "/pages/notfound",
]);

export function createAuthGuard(auth: AuthState) {
  return (to: AuthRouteLocation): AuthGuardResult => {
    if (to.path === "/auth/login") {
      return auth.token ? { name: "dashboard" } : true;
    }
    if (to.meta.public || PUBLIC_PAGES.has(to.path)) {
      return true;
    }

    if (!auth.token) {
      return { name: "login", query: { redirect: to.fullPath } };
    }

    const requiredRole = to.meta.requiresRole;
    if (requiredRole && !auth.isAuthorisedFor(requiredRole)) {
      return { name: "accessDenied" };
    }

    return true;
  };
}
