const assert = require('node:assert');
const fs = require('node:fs');
const vm = require('node:vm');

const snippet = fs.readFileSync(process.argv[2], 'utf8')
    .replace(/^\s*<script>\s*/, '')
    .replace(/\s*<\/script>\s*$/, '');
let activationCount = 0;

const document = {
    addEventListener() {},
    removeEventListener() {},
    getElementById() {
        return {id: 'mautic-tracking-script'};
    },
    dispatchEvent(event) {
        assert.strictEqual(global.MauticFocusUseMauticTrackingConsent, true);
        assert.strictEqual(event.type, 'mautic:tracking-enabled');
        activationCount++;
    }
};
const global = {
    document,
    MauticJS: {
        runtimeReady: true,
        trackingEnabled: true,
        dispatchEvent(name) {
            document.dispatchEvent({type: name});
        }
    }
};
global.window = global;

vm.runInContext(snippet, vm.createContext(global));

assert.strictEqual(global.MauticFocusUseMauticTrackingConsent, true);
assert.strictEqual(activationCount, 1);
