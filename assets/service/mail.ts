/**
 * Typed mail settings API. Only the generic settings endpoint is exposed;
 * report/invoice mail endpoints are deliberately out of scope.
 */
import type { MailSettings } from "../types/api";
import { apiClient } from "./api";

/** Minimal surface the service needs; kept structural for easy injection. */
export interface MailApi {
  get<T>(path: string): Promise<T>;
}

export class MailService {
  constructor(private readonly client: MailApi = apiClient) {}

  settings(): Promise<MailSettings> {
    return this.client.get<MailSettings>("/mail/settings");
  }
}

export const mailService = new MailService();
