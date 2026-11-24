import type { Customer as CustomerDto } from "../types/api";
import type { FetchClient } from "../utils/fetch";
import { ApiService, apiClient } from "./api";

export type Customer = CustomerDto;

/** Demo-only fixture endpoints served by DemoDataController. */
export default class CustomerService extends ApiService<Customer> {
  constructor(client: FetchClient = apiClient) {
    super(client, "/demo/customers");
  }

  getCustomersSmall(): Promise<Customer[]> {
    return this.listFrom("/demo/customers-small");
  }

  getCustomersMedium(): Promise<Customer[]> {
    return this.listFrom("/demo/customers-medium");
  }

  getCustomersLarge(): Promise<Customer[]> {
    return this.listFrom("/demo/customers-large");
  }

  getCustomersXLarge(): Promise<Customer[]> {
    return this.listFrom("/demo/customers-xlarge");
  }
}
