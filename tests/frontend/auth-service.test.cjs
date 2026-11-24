const assert = require("node:assert/strict");
const { test, beforeEach } = require("node:test");

const { AuthService } = require("../../var/test-build/service/auth.js");

const calls = [];

beforeEach(() => {
    calls.length = 0;
    globalThis.document = { cookie: "csrf=csrf-value" };
    globalThis.fetch = async (url, init) => {
        calls.push({ url, init });
        if (url === "/api/logout") {
            return new Response(null, { status: 204 });
        }
        return new Response(
            JSON.stringify({ token: "access-jwt", data: { name: "Ada" } }),
            { status: 200, headers: { "content-type": "application/json" } },
        );
    };
});

test("AuthService.login posts credentials to the local /api/login endpoint", async () => {
    const service = new AuthService();
    const response = await service.login({ username: "ada", password: "secret" });

    assert.equal(response.token, "access-jwt");
    assert.equal(calls[0].url, "/api/login");
    assert.equal(calls[0].init.method, "POST");
    assert.deepEqual(JSON.parse(calls[0].init.body), {
        username: "ada",
        password: "secret",
    });
    assert.equal(calls[0].init.headers["X-CSRF-Token"], "csrf-value");
});

test("AuthService.logout posts to /api/logout with the CSRF header", async () => {
    const service = new AuthService();
    await service.logout();

    assert.equal(calls[0].url, "/api/logout");
    assert.equal(calls[0].init.method, "POST");
    assert.equal(calls[0].init.headers["X-CSRF-Token"], "csrf-value");
});

test("AuthService accepts an injected client so it stays framework-agnostic", async () => {
    const seen = [];
    const service = new AuthService({
        post: async (path, body) => {
            seen.push({ path, body });
            return { token: "injected" };
        },
    });

    const response = await service.login({ username: "grace", password: "pw" });

    assert.equal(response.token, "injected");
    assert.deepEqual(seen, [
        {
            path: "/login",
            body: { username: "grace", password: "pw" },
        },
    ]);
    assert.equal(calls.length, 0);
});
