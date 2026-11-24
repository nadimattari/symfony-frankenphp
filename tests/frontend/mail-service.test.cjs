const assert = require('node:assert/strict');
const { test } = require('node:test');

const { MailService } = require('../../var/test-build/service/mail.js');

test('MailService reads only the generic mail settings endpoint', async () => {
    const calls = [];
    const payload = {
        from: '',
        replyTo: '',
        redirectTo: 'inbox@example.test',
        throttleSeconds: 0,
        isRedirecting: true,
        testMode: true
    };
    const client = {
        async get(path) {
            calls.push(path);
            return payload;
        }
    };

    const service = new MailService(client);
    const settings = await service.settings();

    assert.deepEqual(settings, payload);
    assert.deepEqual(calls, ['/mail/settings']);
});
