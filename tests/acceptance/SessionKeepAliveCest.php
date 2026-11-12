<?php

declare(strict_types=1);

namespace Acceptance;

use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Facebook\WebDriver\Remote\RemoteWebDriver;

final class SessionKeepAliveCest
{
    public function refreshesOnlyAuthenticatedPages(\AcceptanceTester $I): void
    {
        $I->login('admin', 'Maut1cR0cks!');
        // Capture the application's actual timer without waiting half a session.
        $I->executeInSelenium(static function (RemoteWebDriver $driver): void {
            $devTools = new ChromeDevToolsDriver($driver);
            $script   = $devTools->execute('Page.addScriptToEvaluateOnNewDocument', ['source' => <<<'JS'
const originalInterval = window.setInterval;
window.setInterval = function (callback, delay, ...args) {
    if (typeof mauticSessionLifetime !== 'undefined' && delay === mauticSessionLifetime * 1000 / 2) {
        window.keepAliveTick = callback;
    }
    return originalInterval.call(this, callback, delay, ...args);
};
JS]);
            try {
                $driver->navigate()->refresh();
            } finally {
                $devTools->execute('Page.removeScriptToEvaluateOnNewDocument', ['identifier' => $script['identifier']]);
            }
        });
        $I->waitForJS('return typeof window.keepAliveTick === "function";', \AcceptanceTester::TIMEOUT);
        $I->executeJS(<<<'JS'
mQuery(document).on('ajaxComplete.keepAlive', function (event, xhr, settings) {
    const path = new URL(settings.url, location.href).pathname;
    if (path.endsWith('/keep-alive')) {
        window.keepAliveResult = [path, settings.type, xhr.status];
        mQuery(document).off('ajaxComplete.keepAlive');
    }
});
window.keepAliveTick();
JS);
        $I->waitForJS('return Array.isArray(window.keepAliveResult);', \AcceptanceTester::TIMEOUT);
        $I->assertSame(['/s/keep-alive', 'GET', 200], $I->executeJS('return window.keepAliveResult;'));

        // Exercise installation paths without requiring another web server mount.
        $requests = $I->executeJS(<<<'JS'
const requests = [];
const originalPage = location.href;
const originalBase = mauticBaseUrl;
mQuery(document).on('ajaxSend.keepAlive', function (event, xhr, settings) {
    const url = new URL(settings.url, location.href);
    requests.push(url.origin + url.pathname);
    xhr.abort();
});
try {
    for (const base of ['/marketing/', '/marketing/index.php/']) {
        mauticBaseUrl = 'https://configured.example' + base;
        for (const path of ['s/dashboard', 's/login', 'unsubscribe/123']) {
            history.replaceState(null, '', base + path);
            window.keepAliveTick();
        }
    }
} finally {
    mQuery(document).off('ajaxSend.keepAlive');
    history.replaceState(null, '', originalPage);
    mauticBaseUrl = originalBase;
}
return requests;
JS);
        $origin = $I->executeJS('return location.origin;');
        $I->assertSame([$origin.'/marketing/s/keep-alive', $origin.'/marketing/index.php/s/keep-alive'], $requests);
    }
}
