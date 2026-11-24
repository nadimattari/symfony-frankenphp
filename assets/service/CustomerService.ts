export interface Customer {
    id?: number;
    name?: string;
    country?: { name: string; code: string };
    company?: string;
    date?: string;
    status?: string;
    activity?: number;
    representative?: { name: string; image: string };
    balance?: number;
    [key: string]: unknown;
}

export default class CustomerService {
    getCustomersSmall(): Promise<Customer[]> {
        return fetch('demo/data/customers-small.json')
            .then((res) => res.json())
            .then((d) => d.data as Customer[]);
    }

    getCustomersMedium(): Promise<Customer[]> {
        return fetch('demo/data/customers-medium.json')
            .then((res) => res.json())
            .then((d) => d.data as Customer[]);
    }

    getCustomersLarge(): Promise<Customer[]> {
        return fetch('demo/data/customers-large.json')
            .then((res) => res.json())
            .then((d) => d.data as Customer[]);
    }

    getCustomersXLarge(): Promise<Customer[]> {
        return fetch('demo/data/customers-xlarge.json')
            .then((res) => res.json())
            .then((d) => d.data as Customer[]);
    }

    getCustomers(params: Record<string, unknown>): Promise<{ data: Customer[]; total: number }> {
        const queryParams = Object.keys(params)
            .map((k) => encodeURIComponent(k) + '=' + encodeURIComponent(String(params[k])))
            .join('&');
        return fetch('https://www.primefaces.org//demo/data/customers?' + queryParams).then((res) => res.json());
    }
}
