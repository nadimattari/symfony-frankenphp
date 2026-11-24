const assert = require("node:assert/strict");
const { test, beforeEach } = require("node:test");

// The wrapper is compiled from TypeScript into gitignored var/test-build by
// `npm run test:fetch`; no frontend test runner dependency is needed.
const {
    ApiError,
    FetchClient,
    filenameFromContentDisposition,
    readCookie,
} = require("../../var/test-build/utils/fetch.js");

function jwt(expiresAt) {
    const payload = Buffer.from(JSON.stringify({ exp: expiresAt })).toString(
        "base64url",
    );
    return `header.${payload}.signature`;
}

function jsonResponse(body, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { "content-type": "application/json" },
    });
}

let cookies = "";

beforeEach(() => {
    cookies = "";
    globalThis.document = {
        get cookie() {
            return cookies;
        },
        set cookie(value) {
            cookies = value;
        },
    };
});

test("GET resolves JSON, prefixes the base URL and serialises query parameters", async () => {
    const calls = [];
    const client = new FetchClient({
        baseUrl: "/api",
        fetchImpl: async (url, init) => {
            calls.push({ url, init });
            return jsonResponse({ items: [1, 2] });
        },
    });

    const result = await client.get("/products", {
        query: { page: 2, search: "blue shirt" },
    });

    assert.deepEqual(result, { items: [1, 2] });
    assert.equal(calls[0].url, "/api/products?page=2&search=blue+shirt");
    assert.equal(calls[0].init.headers.Accept, "application/json");
});

test("POST sends a JSON body and the CSRF cookie header", async () => {
    cookies = "csrf=abc123; other=x";
    let sent;
    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            sent = { url, init };
            return jsonResponse({ id: 7 }, 201);
        },
    });

    const result = await client.post("/products", { name: "Desk" });

    assert.deepEqual(result, { id: 7 });
    assert.equal(sent.init.method, "POST");
    assert.equal(sent.init.headers["Content-Type"], "application/json");
    assert.equal(sent.init.headers["X-CSRF-Token"], "abc123");
    assert.deepEqual(JSON.parse(sent.init.body), { name: "Desk" });
});

test("PATCH uses the merge-patch content type", async () => {
    let sent;
    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            sent = init;
            return jsonResponse({ patched: true });
        },
    });

    await client.patch("/products/7", { name: "Chair" });

    assert.equal(sent.headers["Content-Type"], "application/merge-patch+json");
    assert.deepEqual(JSON.parse(sent.body), { name: "Chair" });
});

test("204 responses resolve to null and blob downloads keep the server filename", async () => {
    const noContent = new FetchClient({
        fetchImpl: async () => new Response(null, { status: 204 }),
    });
    assert.equal(await noContent.delete("/products/7"), null);

    const downloader = new FetchClient({
        fetchImpl: async () =>
            new Response("PDF", {
                status: 200,
                headers: {
                    "content-disposition":
                        "attachment; filename*=UTF-8''report%202026.pdf",
                },
            }),
    });

    const blob = await downloader.download("/reports/1");
    assert.equal(blob.size, 3);
    assert.equal(
        filenameFromContentDisposition(
            "attachment; filename*=UTF-8''report%202026.pdf",
        ),
        "report 2026.pdf",
    );
    assert.equal(
        filenameFromContentDisposition('attachment; filename="simple.pdf"'),
        "simple.pdf",
    );
    assert.equal(filenameFromContentDisposition(null), "download");
});

test("non-2xx responses throw a typed ApiError with the extracted message", async () => {
    const client = new FetchClient({
        fetchImpl: async () => jsonResponse({ detail: "Invalid product" }, 422),
    });

    await assert.rejects(
        () => client.get("/products/1"),
        (error) => {
            assert.ok(error instanceof ApiError);
            assert.equal(error.status, 422);
            assert.equal(error.message, "Invalid product");
            assert.equal(error.name, "ApiError");
            return true;
        },
    );

    const problem = new FetchClient({
        fetchImpl: async () =>
            jsonResponse({ errors: { name: "Name is required" } }, 400),
    });
    await assert.rejects(
        () => problem.post("/products", {}),
        /Name is required/,
    );

    const plain = new FetchClient({
        fetchImpl: async () => new Response("Bad gateway", { status: 502 }),
    });
    await assert.rejects(() => plain.get("/products"), /Bad gateway/);
});

test("auth hooks can be wired after construction", async () => {
    const calls = [];
    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            calls.push({ url, init });
            return jsonResponse({ ok: true });
        },
    });

    client.setAuthHooks({
        getToken: () => "hooked-token",
        setToken: () => undefined,
    });
    await client.get("/me");

    assert.equal(calls[0].init.headers.Authorization, "Bearer hooked-token");
});

test("readCookie decodes cookie values and returns null when absent", () => {
    cookies = "csrf=a%20b; x=1";
    assert.equal(readCookie("csrf"), "a b");
    assert.equal(readCookie("missing"), null);
});

test("bearer token is attached and an expired token is refreshed before the request", async () => {
    const calls = [];
    let token = jwt(Math.floor(Date.now() / 1000) - 10);
    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            calls.push({ url, init });
            if (url === "/auth/refresh") {
                return jsonResponse({
                    token: jwt(Math.floor(Date.now() / 1000) + 3600),
                });
            }
            return jsonResponse({ ok: true });
        },
        getToken: () => token,
        setToken: (next) => {
            token = next;
        },
        csrfCookieName: "csrf",
        endpoints: { refresh: "/auth/refresh" },
    });

    cookies = "csrf=refresh-csrf";
    await client.get("/me");

    assert.deepEqual(
        calls.map((call) => call.url),
        ["/auth/refresh", "/me"],
    );
    assert.equal(calls[0].init.headers["X-CSRF-Token"], "refresh-csrf");
    assert.match(calls[1].init.headers.Authorization, /^Bearer header\./);
    assert.match(token, /^header\./);
});

test("concurrent 401s share one refresh and each request is retried once", async () => {
    let token = jwt(Math.floor(Date.now() / 1000) + 3600);
    let refreshCalls = 0;
    let releaseRefresh;
    const refreshGate = new Promise((resolve) => {
        releaseRefresh = resolve;
    });
    let seenByPath = {};

    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            if (url === "/auth/refresh") {
                refreshCalls += 1;
                await refreshGate;
                return jsonResponse({
                    token: jwt(Math.floor(Date.now() / 1000) + 3600),
                });
            }
            const path = url.split("?")[0];
            seenByPath[path] = (seenByPath[path] ?? 0) + 1;
            if (seenByPath[path] === 1) {
                return jsonResponse({ message: "expired" }, 401);
            }
            return jsonResponse({ ok: true });
        },
        getToken: () => token,
        setToken: (next) => {
            token = next;
        },
        endpoints: { refresh: "/auth/refresh" },
    });

    cookies = "csrf=csrf-value";
    const first = client.get("/one");
    const second = client.get("/two");
    // Let both requests reach the 401 and join the same in-flight refresh.
    await new Promise((resolve) => setImmediate(resolve));
    await new Promise((resolve) => setImmediate(resolve));
    releaseRefresh();

    assert.deepEqual(await Promise.all([first, second]), [
        { ok: true },
        { ok: true },
    ]);
    assert.equal(refreshCalls, 1);
    assert.deepEqual(seenByPath, { "/one": 2, "/two": 2 });
});

test("failed refresh clears the token, invokes the hook and throws the original 401", async () => {
    let token = jwt(Math.floor(Date.now() / 1000) + 3600);
    let unauthorizedCalls = 0;
    const client = new FetchClient({
        fetchImpl: async (url) => {
            if (url === "/auth/refresh") {
                return jsonResponse({ message: "refresh expired" }, 401);
            }
            return jsonResponse({ message: "Unauthorized" }, 401);
        },
        getToken: () => token,
        setToken: (next) => {
            token = next;
        },
        onUnauthorized: () => {
            unauthorizedCalls += 1;
        },
        endpoints: { refresh: "/auth/refresh" },
    });

    cookies = "csrf=csrf-value";
    await assert.rejects(
        () => client.get("/me"),
        (error) => error instanceof ApiError && error.status === 401,
    );
    assert.equal(unauthorizedCalls, 1);
    assert.equal(token, null);
});

test("a request without a token does not attempt a refresh", async () => {
    const calls = [];
    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            calls.push({ url, init });
            return jsonResponse({ public: true });
        },
    });

    await client.get("/public");

    assert.deepEqual(
        calls.map((call) => call.url),
        ["/public"],
    );
    assert.equal(calls[0].init.headers.Authorization, undefined);
});

test("auth endpoints never attach a bearer token or trigger a refresh loop", async () => {
    const calls = [];
    const client = new FetchClient({
        fetchImpl: async (url, init) => {
            calls.push({ url, init });
            return jsonResponse({ token: "new-token" });
        },
        getToken: () => jwt(0),
        endpoints: {
            login: "/auth/login",
            refresh: "/auth/refresh",
            logout: "/auth/logout",
        },
    });

    await client.post("/auth/login", { username: "a", password: "b" });
    await client.patch("/auth/logout");

    assert.deepEqual(
        calls.map((call) => call.url),
        ["/auth/login", "/auth/logout"],
    );
    assert.equal(calls[0].init.headers.Authorization, undefined);
    assert.equal(refreshCallsIn(calls), 0);
});

function refreshCallsIn(calls) {
    return calls.filter((call) => call.url === "/auth/refresh").length;
}
