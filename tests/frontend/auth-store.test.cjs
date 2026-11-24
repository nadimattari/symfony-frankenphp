const assert = require("node:assert/strict");
const { test, beforeEach } = require("node:test");
const { createPinia, setActivePinia } = require("pinia");

const {
    useAuthStore,
    ROLE_ADMIN,
    ROLE_USER,
} = require("../../var/test-build/store/auth.js");

const storage = new Map();

globalThis.localStorage = {
    getItem: (key) => (storage.has(key) ? storage.get(key) : null),
    setItem: (key, value) => storage.set(key, String(value)),
    removeItem: (key) => storage.delete(key),
    clear: () => storage.clear(),
};
globalThis.document = { cookie: "" };

beforeEach(() => {
    storage.clear();
    globalThis.document.cookie = "";
    setActivePinia(createPinia());
});

test("login persists the token, roles and user, and admin roles authorise everything", async () => {
    let loginRequest;
    globalThis.fetch = async (url, init) => {
        loginRequest = { url, init };
        return new Response(
            JSON.stringify({
                token: "access-jwt",
                data: { name: "Ada", roles: [ROLE_ADMIN] },
            }),
            { status: 200, headers: { "content-type": "application/json" } },
        );
    };

    const store = useAuthStore();
    const response = await store.login({ username: "ada", password: "secret" });

    assert.equal(response.token, "access-jwt");
    assert.equal(store.token, "access-jwt");
    assert.deepEqual(store.roles, [ROLE_ADMIN]);
    assert.equal(store.user, "Ada");
    assert.equal(store.isAuthenticated, true);
    assert.equal(store.isAuthorisedFor(ROLE_USER), true);
    assert.equal(JSON.parse(localStorage.getItem("token")), "access-jwt");
    assert.equal(loginRequest.url, "/api/login");
    assert.deepEqual(JSON.parse(loginRequest.init.body), {
        username: "ada",
        password: "secret",
    });
});

test("logout clears local state and storage without a remote call when remote is false", async () => {
    storage.set("token", JSON.stringify("access-jwt"));
    storage.set("roles", JSON.stringify([ROLE_USER]));
    storage.set("user", JSON.stringify("Grace"));

    const store = useAuthStore();
    assert.equal(store.token, "access-jwt");
    assert.equal(store.isAuthorisedFor(ROLE_ADMIN), false);

    let remoteCalls = 0;
    globalThis.fetch = async () => {
        remoteCalls += 1;
        return new Response(null, { status: 204 });
    };

    store.logout({ remote: false });

    assert.equal(store.token, null);
    assert.deepEqual(store.roles, []);
    assert.equal(store.user, null);
    assert.equal(store.isAuthenticated, false);
    assert.equal(localStorage.getItem("token"), null);
    assert.equal(remoteCalls, 0);
});

test("state is restored from localStorage on a fresh store instance", () => {
    storage.set("token", JSON.stringify("stored-jwt"));
    storage.set("roles", JSON.stringify([ROLE_USER]));

    const store = useAuthStore();

    assert.equal(store.token, "stored-jwt");
    assert.deepEqual(store.roles, [ROLE_USER]);
    assert.equal(store.isAuthorisedFor(ROLE_USER), true);
    assert.equal(store.isAuthorisedFor(ROLE_ADMIN), false);
});
