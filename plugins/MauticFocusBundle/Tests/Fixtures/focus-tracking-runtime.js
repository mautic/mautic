const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

const displayScript = fs.readFileSync(process.argv[2], 'utf8');
const trackingScript = fs.readFileSync(process.argv[3], 'utf8');
const focusId = process.argv[4];

function createBrowser(body = {className: ''}) {
    const documentListeners = {};
    const appendedScripts = [];
    const images = [];

    function createElement(tagName) {
        return {
            tagName: tagName.toUpperCase(),
            className: '',
            children: [],
            appendChild(child) {
                child.parentNode = this;
                this.children.push(child);

                return child;
            },
            removeChild(child) {
                this.children = this.children.filter((candidate) => candidate !== child);
                child.parentNode = null;
            }
        };
    }

    const head = createElement('head');
    const appendToHead = head.appendChild;
    head.appendChild = function (child) {
        appendToHead.call(this, child);
        if (child.tagName === 'SCRIPT') {
            appendedScripts.push(child);
        }

        return child;
    };

    const document = {
        body,
        cookie: '',
        head,
        documentElement: {addEventListener() {}},
        addEventListener(type, listener) {
            documentListeners[type] = listener;
        },
        removeEventListener(type) {
            delete documentListeners[type];
        },
        createElement,
        createTextNode(text) {
            return {text};
        },
        dispatchEvent() {},
        getElementsByClassName() {
            return [];
        },
        getElementsByTagName(tagName) {
            return tagName === 'head' ? [head] : [];
        }
    };
    const browserGlobal = {
        addEventListener() {},
        CustomEvent: function () {},
        Date,
        console,
        decodeURIComponent,
        document,
        encodeURIComponent,
        isNaN,
        parseInt,
        setTimeout,
        clearTimeout
    };
    browserGlobal.Image = function () {
        images.push(this);
    };
    browserGlobal.window = browserGlobal;
    const context = vm.createContext(browserGlobal);

    return {appendedScripts, context, document, documentListeners, images, window: browserGlobal};
}

function execute(script, browser) {
    vm.runInContext(script, browser.context);
}

function item(browser) {
    return browser.window.MauticFocusItems[focusId];
}

{
    const browser = createBrowser();
    browser.window.MauticFocusTrackingQueue = {[focusId]: true};
    execute(displayScript, browser);

    assert.strictEqual(browser.appendedScripts.length, 1, 'queued consent must load tracking once');
    assert.strictEqual(browser.window.MauticFocusTrackingQueue[focusId], undefined, 'queued consent must be consumed');
    assert.strictEqual(item(browser).initialized, false, 'queued consent must delay engagement until tracking loads');

    let trackingEnabledAtRegistration = false;
    item(browser).registerFocusEvent = function () {
        trackingEnabledAtRegistration = item(browser).trackingEnabled;
        item(browser).trackingHooks.onEngage();
    };
    execute(trackingScript, browser);
    assert.strictEqual(item(browser).trackingEnabled, true, 'queued tracking must activate before engagement');
    browser.appendedScripts[0].onload();
    assert.strictEqual(item(browser).initialized, true, 'display must initialize after tracking activation');
    assert.strictEqual(trackingEnabledAtRegistration, true, 'initial engagement must register after tracking activation');
    assert.strictEqual(browser.images.length, 1, 'initial post-consent engagement must send one view');
}

{
    const browser = createBrowser();
    browser.window.MauticFocusTrackingQueue = {[focusId]: true};
    execute(displayScript, browser);

    browser.appendedScripts[0].onerror();
    assert.strictEqual(item(browser).initialized, true, 'failed queued tracking must not block display');
    assert.strictEqual(item(browser).trackingEnabled, false);
}

{
    const browser = createBrowser();
    execute(displayScript, browser);
    const focus = item(browser);

    focus.loadTracking();
    focus.loadTracking();
    assert.strictEqual(browser.appendedScripts.length, 1, 'concurrent activation must share one request');

    browser.appendedScripts[0].onerror();
    focus.loadTracking();
    assert.strictEqual(browser.appendedScripts.length, 2, 'failed activation must be retryable');
}

{
    const browser = createBrowser(null);
    execute(displayScript, browser);
    execute(trackingScript, browser);
    assert.strictEqual(item(browser).trackingEnabled, false, 'tracking must wait for display readiness');

    browser.document.body = {className: ''};
    browser.documentListeners.DOMContentLoaded();
    assert.strictEqual(item(browser).trackingEnabled, true, 'direct tracking must activate after display readiness');
    assert.strictEqual(browser.appendedScripts.length, 0, 'direct tracking must not load another add-on');
}

{
    const browser = createBrowser();
    execute(displayScript, browser);
    const focus = item(browser);
    const hiddenInputs = [];
    const form = {
        appendChild(input) {
            hiddenInputs.push(input);
        },
        querySelector() {
            return hiddenInputs[0] || null;
        }
    };
    const link = {};
    focus.iframeDoc = {
        createElement: browser.document.createElement,
        getElementsByClassName() {
            return [link];
        },
        querySelectorAll() {
            return [form];
        }
    };

    focus.trackingHooks.onEngage();
    assert.strictEqual(browser.images.length, 0, 'pre-activation engagement must not send a view');

    execute(trackingScript, browser);
    execute(trackingScript, browser);
    assert.strictEqual(focus.trackingEnabled, true);
    assert.strictEqual(hiddenInputs.length, 1, 'tracking metadata must be installed once');

    focus.trackingHooks.onEngage();
    focus.trackingHooks.onEngage();
    assert.strictEqual(browser.images.length, 1, 'post-activation engagement must send one view');
}

{
    const browser = createBrowser();
    execute(trackingScript, browser);
    assert.strictEqual(browser.window.MauticFocusItems, undefined, 'tracking without display must fail closed');
    assert.strictEqual(browser.appendedScripts.length, 0);
    assert.strictEqual(browser.images.length, 0);
}
