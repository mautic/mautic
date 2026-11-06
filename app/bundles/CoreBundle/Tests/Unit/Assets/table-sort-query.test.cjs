const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '../../../Assets/js/10.entity.js'), 'utf8');

function sortRequest(baseUrl, name = 'contacts', orderby = 'l.lastname', tmpl = 'list') {
    const calls = [];
    const context = vm.createContext({
        Mautic: {loadContent: (...args) => calls.push(args)}, MauticVars: {},
        window: {location: {pathname: '/s/contacts/4'}}
    });
    vm.runInContext(source, context);
    context.Mautic.reorderTableData(name, orderby, tmpl, '#contact-table', baseUrl);
    assert.equal(calls.length, 1);
    assert.equal(calls[0][2], 'POST');
    assert.equal(calls[0][3], '#contact-table');
    return new URL(calls[0][0], 'https://example.test');
}

test('sort preserves an existing query parameter and adds separate sort parameters', () => {
    const url = sortRequest('/s/contacts?search=alice%2Bbob');
    assert.equal(url.searchParams.get('search'), 'alice+bob');
    assert.equal(url.searchParams.get('tmpl'), 'list');
    assert.equal(url.searchParams.get('name'), 'contacts');
    assert.equal(url.searchParams.get('orderby'), 'l.lastname');
});

test('sort preserves multiple query parameters and encoded ordering values', () => {
    const url = sortRequest('/s/contacts?category=3&search=A%26B', 'contacts', 'custom.field+name');
    assert.equal(url.searchParams.get('search'), 'A&B');
    assert.equal(url.searchParams.get('category'), '3');
    assert.equal(url.searchParams.get('orderby'), 'custom.field+name');
});

test('a base URL without a query still receives a normal query string', () => {
    const url = sortRequest('/s/contacts');
    assert.equal(url.pathname, '/s/contacts');
    assert.equal(url.search, '?tmpl=list&name=contacts&orderby=l.lastname');
});

test('sorting the current list still resets pagination to the first page', () => {
    assert.equal(sortRequest(undefined).pathname, '/s/contacts/1');
});

test('an empty parameter list leaves an existing query untouched', () => {
    const url = sortRequest('/s/contacts?category=3', '', '', '');
    assert.equal(url.search, '?category=3');
});
