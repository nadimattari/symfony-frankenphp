/**
 * Generic formatting helpers ported from Lamiral's Tier A `common.js`.
 * Locale, timezone and currency are configurable instead of hard-coded to
 * `en-EN` / `en-GB` / `Indian/Mauritius`. Domain-specific portal helpers are
 * deliberately excluded.
 */

export interface FormatConfig {
  locale: string;
  timeZone: string;
  currency: string;
}

export const DEFAULT_FORMAT_CONFIG: FormatConfig = {
  locale: "en-GB",
  timeZone: "UTC",
  currency: "USD",
};

let activeConfig: FormatConfig = { ...DEFAULT_FORMAT_CONFIG };

export function configureFormat(config: Partial<FormatConfig>): void {
  activeConfig = { ...activeConfig, ...config };
}

export function getFormatConfig(): FormatConfig {
  return { ...activeConfig };
}

export function resetFormatConfig(): void {
  activeConfig = { ...DEFAULT_FORMAT_CONFIG };
}

export function isNumber(value: unknown): boolean {
  if (typeof value === "number") {
    return true;
  }
  if (typeof value === "string") {
    return value.trim() !== "" && !Number.isNaN(Number(value));
  }
  return false;
}

export function formatNumber(
  value: number,
  fraction = 2,
  locale = activeConfig.locale,
): string {
  return new Intl.NumberFormat(locale, {
    minimumFractionDigits: fraction,
    maximumFractionDigits: fraction,
  }).format(value);
}

export function formatCurrency(
  value: number,
  currency = activeConfig.currency,
  locale = activeConfig.locale,
): string {
  return new Intl.NumberFormat(locale, {
    style: "currency",
    currency,
  }).format(value);
}

/** `YYYY-MM-DD[- HH:mm]`-style output derived from the configured locale. */
export function formatDate(
  date: string | number | Date | null | undefined,
  withTime = false,
  locale = activeConfig.locale,
  timeZone = activeConfig.timeZone,
): string {
  if (date === null || date === undefined) {
    return "";
  }

  try {
    const parsed = date instanceof Date ? date : new Date(date);
    if (Number.isNaN(parsed.getTime())) {
      return String(date);
    }

    const options: Intl.DateTimeFormatOptions = {
      year: "numeric",
      month: "numeric",
      day: "numeric",
      timeZone,
    };
    if (withTime) {
      options.hour = "2-digit";
      options.minute = "2-digit";
      options.hourCycle = "h23";
    }

    return new Intl.DateTimeFormat(locale, options)
      .format(parsed)
      .replace(/\//g, "-")
      .replace(/,/g, "");
  } catch {
    return String(date);
  }
}
