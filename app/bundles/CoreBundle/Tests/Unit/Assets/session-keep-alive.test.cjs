const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '../../../Assets/js/1.core.js'), 'utf8');

function keepAliveRequests(baseUrl, pathname) {
    const ready = [];
    const timers = [];
    const requests = [];
    const chain = {
        on() { return this; }, off() { return this; }, each() { return this; },
        find() { return this; }, ajaxComplete() {}, ajaxStop() {},
        ready(callback) { ready.push(callback); }
    };
    const query = () => chain;
    query.ajaxSetup = () => {};
    query.get = (url) => { requests.push(url); return {fail() {}}; };
    const context = vm.createContext({
        jQuery: {noConflict: () => query}, document: {},
        window: {location: {pathname, href: `http://localhost${pathname}`}}, URL,
        localStorage: {getItem: () => null},
        mauticBaseUrl: baseUrl, mauticSessionLifetime: 1200,
        setInterval: (callback, delay) => timers.push({callback, delay}), console
    });
    vm.runInContext(source, context);
    ready.forEach((callback) => callback());
    assert.equal(timers.length, 1);
    assert.equal(timers[0].delay, 600000);
    timers[0].callback();
    return requests;
}

for (const baseUrl of ['/', '/marketing/', '/marketing/index.php/', 'https://example.com/', 'https://example.com/marketing/', 'https://example.com/marketing/index.php/']) {
    const basePath = new URL(baseUrl, 'http://localhost/').pathname;
    test(`keep-alive uses the installation URL ${baseUrl}`, () => {
        assert.deepEqual(keepAliveRequests(baseUrl, `${basePath}s/dashboard`), [`${baseUrl}s/keep-alive`]);
    });

    test(`login and public pages do not refresh sessions under ${baseUrl}`, () => {
        assert.deepEqual(keepAliveRequests(baseUrl, `${basePath}s/login`), []);
        assert.deepEqual(keepAliveRequests(baseUrl, `${basePath}unsubscribe/123`), []);
    });
}
