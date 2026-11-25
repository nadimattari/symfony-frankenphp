# npm audit triage (2026-11-25)

Status: **accepted for the current toolchain; upgrades deferred**

Source spec: [SPEC-npm-audit-triage.md](../../spec/SPEC-npm-audit-triage.md)

## Snapshot and evidence

Commands were run in the project's `sf-node` container (Node 20). No install, `npm audit fix`, `npm update` or lock regeneration was performed.

| Command | Result |
|---|---|
| `npm audit --omit=dev --json` | exit 0, **0 vulnerabilities** |
| `npm audit --json` | exit 1, **44 vulnerabilities** |

Full-audit severity breakdown: **3 critical, 20 high, 16 moderate, 5 low** (44 total). All 44 entries report a `fixAvailable` path; 9 of those require a semver-major change (Encore 5 or `webpack-notifier` 1.11.1). This record intentionally does not take those upgrades.

Production dependencies as shipped are clean. Every finding below is in the build/dev toolchain.

## Reachability rationale

- `npm run build` executes Babel, Webpack and loaders inside `sf-node` and emits static JS/CSS into `public/build`. The deployed artifact contains those generated files plus `entrypoints.json`; it does not contain the dev server, Express/body-parser middleware, proxy/websocket handlers, notifier/editor helpers or Babel runtime packages.
- The dev server is not part of the production image and is not exposed as a runtime service. Middleware findings (`proxy-addr`, `express`, `webpack-dev-server`, `websocket-driver`, `ws`, etc.) are therefore not reachable from a deployment request.
- Build tools do execute on the maintainer/CI machine with the repository as input. This is a trusted-build assumption: do not build untrusted source in a shared or privileged environment. The dedicated `sf-node` container bounds that surface.
- A dev-only advisory becomes in scope if a package moves into `dependencies`, a dev server is exposed beyond loopback, or an advisory is shown to affect emitted assets. None of those conditions holds in this snapshot.

## Findings (44)

Advisory column lists the first four IDs from the audit snapshot; `+` means the package has additional advisories.

| Package | Severity | Status | Advisory IDs | Affected range |
|---|---|---|---|---|
| `@babel/core` | low | direct | GHSA-4x5r-pxfx-6jf8 | `<=7.29.0` |
| `@babel/helpers` | moderate | transitive | GHSA-968p-4wvh-cqc8 | `<7.26.10` |
| `@babel/plugin-transform-modules-systemjs` | high | transitive | GHSA-fv7c-fp4j-7gwp | `7.12.0 - 7.29.0` |
| `@babel/runtime` | moderate | transitive | GHSA-968p-4wvh-cqc8 | `<7.26.10` |
| `@symfony/webpack-encore` | moderate | direct | GHSA-5c6j-r48x-rmvq, GHSA-qj8w-gfj5-8c6v, GHSA-m5mm-p8h7-m29v, GHSA-9jgg-88mc-972h, + | `*` |
| `ajv` | moderate | transitive | GHSA-2g4f-4pwh-qvx6 | `<6.14.0 \|\| >=7.0.0-alpha.0 <8.18.0` |
| `body-parser` | high | transitive | GHSA-qwcr-r2fm-qrc7, GHSA-v422-hmwv-36x6, GHSA-27r3-jxmh-p828, GHSA-w7fw-mjwx-w883, + | `<=1.20.8 \|\| 2.0.0-beta.1 - 2.0.2` |
| `brace-expansion` | high | transitive | GHSA-v6h2-p8h4-qcjw, GHSA-f886-m6hf-6m8v, GHSA-3jxr-9vmj-r5cp, GHSA-mh99-v99m-4gvg, + | `<=1.1.20` |
| `braces` | high | transitive | GHSA-grv7-fg5c-xmjg | `<3.0.3` |
| `browserslist` | high | transitive | GHSA-c83g-rgw3-j3cx, GHSA-73wf-gq98-2v4g | `<=4.28.6` |
| `colord` | moderate | transitive | GHSA-2wm5-q62r-hmrv | `<2.9.4` |
| `compression` | high | transitive | GHSA-vc2v-76pw-4v95, GHSA-76c9-3jph-rj3q | `<=1.8.1` |
| `cookie` | low | transitive | GHSA-pxg6-pf52-xh8x | `<0.7.0` |
| `css-minimizer-webpack-plugin` | moderate | transitive | GHSA-5c6j-r48x-rmvq, GHSA-qj8w-gfj5-8c6v, GHSA-m5mm-p8h7-m29v | `1.1.4 - 7.0.4` |
| `express` | high | transitive | GHSA-qw6h-vgh9-j6wx, GHSA-qwcr-r2fm-qrc7, GHSA-v422-hmwv-36x6, GHSA-27r3-jxmh-p828, + | `<=4.22.2 \|\| 5.0.0-alpha.1 - 5.0.1` |
| `follow-redirects` | moderate | transitive | GHSA-r4q5-vmmm-2653 | `<=1.15.11` |
| `http-proxy-middleware` | high | transitive | GHSA-c7qv-q95q-8v27, GHSA-9gqv-wp59-fq42, GHSA-4www-5p9h-95mh, GHSA-64mm-vxmg-q3vj | `<=2.0.10-beta.0` |
| `immutable` | high | transitive | GHSA-wf6x-7x77-mvgw, GHSA-xvcm-6775-5m9r, GHSA-v56q-mh7h-f735 | `4.0.0-rc.1 - 4.3.8` |
| `launch-editor` | high | transitive | GHSA-c27g-q93r-2cwf, GHSA-v6wh-96g9-6wx3, GHSA-698v-4r3x-3vcv, GHSA-r825-w8mm-5pxw, + | `<=2.14.1` |
| `lodash` | high | transitive | GHSA-r5fr-rjxr-66jc, GHSA-xxjr-mmjv-4gpg | `4.0.0 - 4.17.23` |
| `micromatch` | moderate | transitive | GHSA-952p-6rrq-rcjv | `<4.0.8` |
| `node-forge` | high | transitive | GHSA-554w-wpv2-vw27, GHSA-5gfm-wpxj-wjgq, GHSA-65ch-62r8-g69g, GHSA-2328-f5f3-gj25, + | `<=1.4.2` |
| `node-notifier` | moderate | transitive | GHSA-w5hq-g745-h8pq | `7.0.0 - 10.0.1` |
| `on-headers` | low | transitive | GHSA-76c9-3jph-rj3q | `<1.1.0` |
| `path-to-regexp` | high | transitive | GHSA-9wv6-86v2-598j, GHSA-rhx6-c78j-4q9w, GHSA-37ch-88jc-xwx2 | `<=0.1.12` |
| `picomatch` | high | transitive | GHSA-3v7f-55p6-f55p, GHSA-c2c7-rcm5-vvqj | `<=2.3.1` |
| `postcss-selector-parser` | moderate | transitive | GHSA-rj75-hqrm-r3gf | `<6.1.5` |
| `proxy-addr` | critical | transitive | GHSA-jqcg-44mw-7w3h | `1.1.0 - 2.0.7` |
| `qs` | moderate | transitive | GHSA-w7fw-mjwx-w883, GHSA-6rw7-vpxm-498p, GHSA-4mjr-xmp4-gh2g, GHSA-6pq8-jhm2-2vcw | `<=6.16.0` |
| `send` | low | transitive | GHSA-m6fv-jmcg-4jfg | `<0.19.0` |
| `serialize-javascript` | high | transitive | GHSA-5c6j-r48x-rmvq, GHSA-qj8w-gfj5-8c6v, GHSA-m5mm-p8h7-m29v | `<=7.1.2` |
| `serve-static` | low | transitive | GHSA-cm22-4g7w-348p, GHSA-m6fv-jmcg-4jfg | `<=1.16.0` |
| `shell-quote` | critical | transitive | GHSA-w7jw-789q-3m8p, GHSA-395f-4hp3-45gv | `<=1.8.4` |
| `sockjs` | moderate | transitive | GHSA-w5hq-g745-h8pq | `>=0.3.17` |
| `svgo` | high | transitive | GHSA-xpqw-6gx7-v673, GHSA-2p49-hgcm-8545, GHSA-4vpr-x523-8j87, GHSA-w27v-7q3p-w38r | `3.0.0 - 3.3.4` |
| `terser-webpack-plugin` | moderate | transitive | GHSA-5c6j-r48x-rmvq, GHSA-qj8w-gfj5-8c6v, GHSA-m5mm-p8h7-m29v | `4.2.1 - 5.3.16` |
| `tmp` | high | transitive | GHSA-52f5-9888-hmc6, GHSA-ph9p-34f9-6g65 | `<=0.2.5` |
| `uuid` | moderate | transitive | GHSA-w5hq-g745-h8pq | `<11.1.1` |
| `webpack` | moderate | direct | GHSA-4vvj-4cpr-p986, GHSA-8fgc-7cc6-rx7x, GHSA-38r7-794h-5758 | `5.0.0-alpha.0 - 5.104.0` |
| `webpack-dev-middleware` | high | transitive | GHSA-g84c-rxfj-3j2c | `<7.4.5` |
| `webpack-dev-server` | high | transitive | GHSA-9jgg-88mc-972h, GHSA-4v9v-hfq4-rm2v, GHSA-79cf-xcqc-c78w, GHSA-mx8g-39q3-5c79, + | `<=5.2.7` |
| `webpack-notifier` | moderate | direct | GHSA-w5hq-g745-h8pq | `>=1.12.0` |
| `websocket-driver` | critical | transitive | GHSA-mp7j-qc5w-4988, GHSA-xv26-6w52-cph6 | `<=0.7.4` |
| `ws` | high | transitive | GHSA-3h5v-q93c-6h6q, GHSA-58qx-3vcg-4xpx, GHSA-96hv-2xvq-fx4p | `8.0.0 - 8.20.1` |

## Tool groups and decisions

Every finding above belongs to exactly one group below. "Accept" applies to the current release only; it is not a claim that the advisory is harmless in a different deployment context.

| Group | Packages | Reachability | Decision |
|---|---|---|---|
| A — Direct build entrypoints (4) | `@babel/core`, `@symfony/webpack-encore`, `webpack`, `webpack-notifier` | Build-time only; no runtime path in the deployed artifact | **Accept** for current release; **defer** upgrades. Encore 5 and `webpack-notifier` 1.11.1 are semver-major and out of scope here. |
| B — Babel helpers/runtime (3) | `@babel/helpers`, `@babel/plugin-transform-modules-systemjs`, `@babel/runtime` | Build-time transforms only; Babel runtime here is not shipped to browsers by this build | **Accept** for current release; **defer** to the next Babel/toolchain maintenance pass. Fixes exist without a major bump. |
| C — Dev-server/proxy/websocket stack (18) | `proxy-addr`, `express`, `body-parser`, `cookie`, `qs`, `path-to-regexp`, `send`, `serve-static`, `compression`, `on-headers`, `http-proxy-middleware`, `webpack-dev-server`, `webpack-dev-middleware`, `sockjs`, `websocket-driver`, `ws`, `follow-redirects`, `node-forge` | Local dev server / HMR only; excluded from production image and deployed request path. Includes the critical `proxy-addr` and `websocket-driver` advisories | **Accept** for current release; **defer** until a planned dev-server or Encore upgrade. Do not expose the dev server beyond loopback. |
| D — Editor/notifier/temp helpers (5) | `shell-quote`, `launch-editor`, `node-notifier`, `tmp`, `uuid` | Local CLI/build host only; includes the critical `shell-quote` advisory | **Accept** for current release; **defer**. Upgrading this group is tied to `webpack-notifier` and Encore major versions. |
| E — Asset optimisers and parsers (14) | `ajv`, `brace-expansion`, `braces`, `browserslist`, `colord`, `css-minimizer-webpack-plugin`, `immutable`, `lodash`, `micromatch`, `picomatch`, `postcss-selector-parser`, `serialize-javascript`, `svgo`, `terser-webpack-plugin` | Build-time optimisation/parsing only; findings are not reachable from emitted static assets in this snapshot | **Accept** for current release; **defer** to the next toolchain maintenance pass. Most fixes are non-major and should be batched, not cherry-picked under time pressure. |

## Review trigger and owner

Owner: the project maintainer. Re-triage **before any release/tag decision** and in the next scheduled toolchain maintenance window (December 2026 or sooner) if any of the following occurs:

- `npm audit --omit=dev` becomes non-zero, or a dev-listed package moves into `dependencies`;
- the dev server is exposed beyond loopback or used on untrusted input;
- a critical advisory is shown to affect emitted assets or the build host's trust boundary;
- an Encore 5 / `webpack-notifier` 1.11.1 upgrade is being considered.

No forced major upgrade is part of this initiative. `package.json` and `package-lock.json` remain unchanged by this triage.
