const assert = require("node:assert/strict");
const { test } = require("node:test");

const { createAuthGuard, PUBLIC_PAGES } = require("../../var/test-build/router/guards.js");

function auth({ token = null, roles = [] } = {}) {
    return {
        token,
        roles,
        isAuthorisedFor(role) {
            return roles.includes("ROLE_ADMIN") || roles.includes(role);
        },
    };
}

function route(path, meta = {}) {
    return { path, fullPath: path, meta };
}

test("public pages are always reachable without a token", () => {
    const guard = createAuthGuard(auth());
    assert.ok(PUBLIC_PAGES.has("/auth/login"));

    for (const path of PUBLIC_PAGES) {
        assert.equal(guard(route(path)), true, `${path} should be public`);
    }
    assert.equal(guard(route("/pages/anything", { public: true })), true);
});

test("a protected route without a token redirects to login with the redirect target", () => {
    const guard = createAuthGuard(auth());

    assert.deepEqual(guard(route("/pages/crud", { requiresAuth: true })), {
        name: "login",
        query: { redirect: "/pages/crud" },
    });
});

test("a protected route with a token is allowed", () => {
    const guard = createAuthGuard(auth({ token: "jwt", roles: ["ROLE_USER"] }));

    assert.equal(guard(route("/pages/crud", { requiresAuth: true })), true);
});

test("a role-protected route redirects unauthenticated users to login", () => {
    const guard = createAuthGuard(auth());

    assert.deepEqual(
        guard(route("/pages/admin", { requiresAuth: true, requiresRole: "ROLE_ADMIN" })),
        { name: "login", query: { redirect: "/pages/admin" } },
    );
});

test("a role-protected route denies authenticated users without that role", () => {
    const guard = createAuthGuard(auth({ token: "jwt", roles: ["ROLE_USER"] }));

    assert.deepEqual(
        guard(route("/pages/admin", { requiresAuth: true, requiresRole: "ROLE_ADMIN" })),
        { name: "accessDenied" },
    );
});

test("an admin can access any role-protected route", () => {
    const guard = createAuthGuard(auth({ token: "jwt", roles: ["ROLE_ADMIN"] }));

    assert.equal(
        guard(route("/pages/admin", { requiresAuth: true, requiresRole: "ROLE_CASHIER" })),
        true,
    );
});

test("an authenticated user visiting the login page is sent to the dashboard", () => {
    const guard = createAuthGuard(auth({ token: "jwt", roles: ["ROLE_USER"] }));

    assert.deepEqual(guard(route("/auth/login")), { name: "dashboard" });
});
