export interface Country {
    name: string;
    code: string;
    [key: string]: unknown;
}

export default class CountryService {
    getCountries(): Promise<Country[]> {
        return fetch('demo/data/countries.json')
            .then((res) => res.json())
            .then((d) => d.data as Country[]);
    }
}
