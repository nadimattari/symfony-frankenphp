import type { Country as CountryDto } from "../types/api";
import type { FetchClient } from "../utils/fetch";
import { ApiService, apiClient } from "./api";

export type Country = CountryDto;

/** Demo-only fixture endpoint served by DemoDataController. */
export default class CountryService extends ApiService<Country> {
  constructor(client: FetchClient = apiClient) {
    super(client, "/demo/countries");
  }

  getCountries(): Promise<Country[]> {
    return this.list();
  }
}
