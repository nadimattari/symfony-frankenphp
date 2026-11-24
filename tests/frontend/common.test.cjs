const assert = require("node:assert/strict");
const { test, afterEach } = require("node:test");

const {
    DEFAULT_FORMAT_CONFIG,
    configureFormat,
    formatCurrency,
    formatDate,
    formatNumber,
    getFormatConfig,
    isNumber,
    resetFormatConfig,
} = require("../../var/test-build/utils/common.js");

afterEach(() => {
    resetFormatConfig();
});

test("isNumber accepts numbers and numeric strings but rejects blanks and objects", () => {
    assert.equal(isNumber(42), true);
    assert.equal(isNumber("42.5"), true);
    assert.equal(isNumber(""), false);
    assert.equal(isNumber("   "), false);
    assert.equal(isNumber("abc"), false);
    assert.equal(isNumber(null), false);
    assert.equal(isNumber({}), false);
});

test("formatNumber honours the supplied locale and fraction digits", () => {
    assert.equal(formatNumber(1234.5, 2, "en-US"), "1,234.50");
    assert.equal(formatNumber(1234.5, 0, "en-US"), "1,235");
    assert.equal(formatNumber(1234.5, 2, "de-DE"), "1.234,50");
});

test("formatDate uses the configured locale and timezone", () => {
    configureFormat({ locale: "en-GB", timeZone: "Indian/Mauritius" });

    assert.equal(formatDate("2026-11-24T10:30:00Z"), "24-11-2026");
    assert.equal(formatDate("2026-11-24T10:30:00Z", true), "24-11-2026 14:30");
});

test("formatDate defaults to UTC and a US-compatible day-month-year output", () => {
    assert.deepEqual(DEFAULT_FORMAT_CONFIG, {
        locale: "en-GB",
        timeZone: "UTC",
        currency: "USD",
    });
    assert.equal(formatDate("2026-11-24T23:30:00Z"), "24-11-2026");
    assert.equal(formatDate("2026-11-24T23:30:00Z", true), "24-11-2026 23:30");
});

test("formatDate returns the original input for invalid dates", () => {
    assert.equal(formatDate("not-a-date"), "not-a-date");
    assert.equal(formatDate(null), "");
});

test("formatCurrency uses the configured currency and locale", () => {
    assert.equal(formatCurrency(1234.5), "US$1,234.50");
    configureFormat({ locale: "de-DE", currency: "EUR" });
    assert.equal(formatCurrency(1234.5), "1.234,50\u00a0€");
    assert.equal(formatCurrency(1234.5, "USD", "en-US"), "$1,234.50");
});

test("configureFormat merges partial overrides and reset restores defaults", () => {
    configureFormat({ timeZone: "Asia/Tokyo" });
    assert.deepEqual(getFormatConfig(), {
        locale: "en-GB",
        timeZone: "Asia/Tokyo",
        currency: "USD",
    });
    assert.equal(formatDate("2026-11-24T10:30:00Z", true), "24-11-2026 19:30");

    resetFormatConfig();
    assert.deepEqual(getFormatConfig(), DEFAULT_FORMAT_CONFIG);
});
