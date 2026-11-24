const assert = require('node:assert/strict');
const { test, beforeEach } = require('node:test');

const { FetchClient } = require('../../var/test-build/utils/fetch.js');
const { collectionData } = require('../../var/test-build/service/api.js');
const ProductService = require('../../var/test-build/service/ProductService.js').default;
const CustomerService = require('../../var/test-build/service/CustomerService.js').default;

function clientReturning(urlsAndBodies) {
    const calls = [];
    return {
        calls,
        client: new FetchClient({
            baseUrl: '/api',
            fetchImpl: async (url) => {
                calls.push(url);
                const body = urlsAndBodies[url];
                if (body === undefined) {
                    return new Response(JSON.stringify({ detail: 'Not found' }), { status: 404 });
                }
                return new Response(JSON.stringify(body), {
                    status: 200,
                    headers: { 'content-type': 'application/json' }
                });
            }
        })
    };
}

beforeEach(() => {
    globalThis.document = { cookie: '', get cookie() { return ''; }, set cookie(_value) {} };
});

test('collectionData accepts plain, Hydra and array payloads', () => {
    assert.deepEqual(collectionData({ data: [1, 2], total: 2 }), [1, 2]);
    assert.deepEqual(collectionData({ 'hydra:member': [3], 'hydra:totalItems': 1 }), [3]);
    assert.deepEqual(collectionData([4]), [4]);
    assert.deepEqual(collectionData(null), []);
});

test('ProductService reads the local demo API, not a remote PrimeFaces URL', async () => {
    const { client, calls } = clientReturning({
        '/api/demo/products': { data: [{ id: '1000', name: 'Bamboo Watch' }] },
        '/api/demo/products-small': { data: [{ id: '1001', name: 'Watch' }] },
        '/api/demo/products-orders-small': { data: [{ id: '1002', name: 'Ordered' }] }
    });
    const service = new ProductService(client);

    assert.deepEqual(await service.getProducts(), [{ id: '1000', name: 'Bamboo Watch' }]);
    assert.equal((await service.getProductsSmall())[0].name, 'Watch');
    assert.equal((await service.getProductsWithOrdersSmall())[0].name, 'Ordered');
    assert.deepEqual(calls, [
        '/api/demo/products',
        '/api/demo/products-small',
        '/api/demo/products-orders-small'
    ]);
});

test('CustomerService exposes the typed CRUD pattern against local endpoints', async () => {
    const { client, calls } = clientReturning({
        '/api/demo/customers-large': { data: [{ id: 1000, name: 'James Butt' }] },
        '/api/demo/customers/1000': { id: 1000, name: 'James Butt' }
    });
    const service = new CustomerService(client);

    assert.deepEqual(await service.getCustomersLarge(), [{ id: 1000, name: 'James Butt' }]);
    assert.equal((await service.get('1000')).name, 'James Butt');
    assert.deepEqual(calls, ['/api/demo/customers-large', '/api/demo/customers/1000']);
});
