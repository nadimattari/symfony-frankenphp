export interface Product {
    id?: string | number;
    name?: string;
    image?: string;
    price?: number;
    category?: string;
    rating?: number;
    inventoryStatus?: string;
    [key: string]: unknown;
}

export default class ProductService {
    getProductsSmall(): Promise<Product[]> {
        return fetch('demo/data/products-small.json')
            .then((res) => res.json())
            .then((d) => d.data as Product[]);
    }

    getProducts(): Promise<Product[]> {
        return fetch('demo/data/products.json')
            .then((res) => res.json())
            .then((d) => d.data as Product[]);
    }

    getProductsWithOrdersSmall(): Promise<Product[]> {
        return fetch('demo/data/products-orders-small.json')
            .then((res) => res.json())
            .then((d) => d.data as Product[]);
    }
}
