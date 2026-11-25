/**
 * Typed auth store. The access token and roles live in localStorage (the
 * accepted XSS trade-off documented in the Task 22 checklist); the refresh
 * token stays in an httpOnly cookie set by Symfony and is never visible here.
 */
import { defineStore } from "pinia";

import { authService } from "../service/auth";
import type { AuthResponse, LoginRequest, Role } from "../types/api";

export const ROLE_ADMIN: Role = "ROLE_ADMIN";
export const ROLE_USER: Role = "ROLE_USER";

const TOKEN_KEY = "token";
const ROLES_KEY = "roles";
const USER_KEY = "user";

function readJson<T>(key: string, fallback: T): T {
  try {
    const raw = localStorage.getItem(key);
    return raw === null ? fallback : (JSON.parse(raw) as T);
  } catch {
    return fallback;
  }
}

function writeJson(key: string, value: unknown): void {
  if (value === null || value === undefined) {
    localStorage.removeItem(key);
    return;
  }
  localStorage.setItem(key, JSON.stringify(value));
}

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: readJson<string | null>(USER_KEY, null),
    roles: readJson<Role[]>(ROLES_KEY, []),
    token: readJson<string | null>(TOKEN_KEY, null),
  }),
  getters: {
    isAuthenticated: (state): boolean => Boolean(state.token),
  },
  actions: {
    saveToken(token: string | null): void {
      this.token = token;
      writeJson(TOKEN_KEY, token);
    },
    saveRoles(roles: Role[]): void {
      this.roles = roles;
      writeJson(ROLES_KEY, roles);
    },
    saveUser(user: string | null): void {
      this.user = user;
      writeJson(USER_KEY, user);
    },
    /** Used by both login and a successful silent refresh. */
    applyAuthResponse(response: AuthResponse): void {
      this.saveToken(response.token);
      const identity = response.data;
      if (!identity) {
        return;
      }
      if (typeof identity.name === "string") {
        this.saveUser(identity.name);
      }
      if (Array.isArray(identity.roles)) {
        this.saveRoles(identity.roles);
      }
    },
    async login(credentials: LoginRequest): Promise<AuthResponse> {
      const response = await authService.login(credentials);
      this.applyAuthResponse(response);
      return response;
    },
    isAuthorisedFor(role: Role): boolean {
      if (this.roles.includes(ROLE_ADMIN)) {
        return true;
      }
      return this.roles.includes(role);
    },
    logout({ remote = true }: { remote?: boolean } = {}): void {
      if (remote && this.token) {
        // Best effort: local state is cleared immediately, never waiting on
        // the network. The refresh cookie is revoked server-side.
        authService.logout().catch(() => undefined);
      }
      this.saveToken(null);
      this.saveRoles([]);
      this.saveUser(null);
    },
  },
});
