import type { Product as ProductDto } from "../types/api";
import type { FetchClient } from "../utils/fetch";
import { ApiService, apiClient } from "./api";

/** Re-exported so existing views can import the DTO from the service. */
export type Product = ProductDto;

/** Demo-only fixture endpoints served by DemoDataController. */
export default class ProductService extends ApiService<Product> {
  constructor(client: FetchClient = apiClient) {
    super(client, "/demo/products");
  }

  getProducts(): Promise<Product[]> {
    return this.list();
  }

  getProductsSmall(): Promise<Product[]> {
    return this.listFrom("/demo/products-small");
  }

  getProductsWithOrdersSmall(): Promise<Product[]> {
    return this.listFrom("/demo/products-orders-small");
  }
}
