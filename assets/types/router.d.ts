/** Typed meta fields consumed by assets/router/guards.ts. */
import type { Role } from "./api";

declare module "vue-router" {
  interface RouteMeta {
    requiresAuth?: boolean;
    requiresRole?: Role;
    public?: boolean;
  }
}

export {};
