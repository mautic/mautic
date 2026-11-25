<?php

declare(strict_types=1);

namespace Acceptance;

final class ModalTabPersistenceCest
{
    public function modalTabsKeepTheirConfiguredDefault(\AcceptanceTester $I): void
    {
        $I->amOnPage('/s/login');
        $I->waitForJS("return window.Mautic && typeof window.Mautic.rememberActiveTabs === 'function';", \AcceptanceTester::TIMEOUT);

        /** @var array{modal: string, page: string} $activeTabs */
        $activeTabs = $I->executeJS(<<<'JS'
const existingTabCount = mQuery('.nav-tabs').length;
const existingPageTabCount = mQuery('.nav-tabs').filter(function() {
    return !mQuery(this).closest('.modal').length;
}).length;
const pageStorageKey = 'modal-tab-persistence-page';
const modalStorageKey = 'modal-tab-persistence-modal';
const pageTabKey = 'mautic-tab-id-' + globalThis.location.pathname + '-' + existingPageTabCount;
const modalTabKey = 'mautic-tab-id-' + globalThis.location.pathname + '-' + (existingTabCount + 1);

mQuery('body').append(
    '<div id="page-tab-persistence-fixture">'
    + '<ul class="nav nav-tabs">'
    + '<li class="nav-item"><a class="nav-link active" href="#page-details" role="tab" data-bs-toggle="tab">Details</a></li>'
    + '<li class="nav-item"><a class="nav-link" href="#page-features" role="tab" data-bs-toggle="tab">Features</a></li>'
    + '</ul>'
    + '<div class="tab-content">'
    + '<div class="tab-pane fade show active" id="page-details"></div>'
    + '<div class="tab-pane fade" id="page-features"></div>'
    + '</div>'
    + '</div>'
    + '<div class="modal" id="modal-tab-persistence-fixture">'
    + '<ul class="nav nav-tabs">'
    + '<li class="nav-item"><a class="nav-link active" href="#modal-details" role="tab" data-bs-toggle="tab">Details</a></li>'
    + '<li class="nav-item"><a class="nav-link" href="#modal-features" role="tab" data-bs-toggle="tab">Features</a></li>'
    + '</ul>'
    + '<div class="tab-content">'
    + '<div class="tab-pane fade show active" id="modal-details"></div>'
    + '<div class="tab-pane fade" id="modal-features"></div>'
    + '</div>'
    + '</div>'
);

sessionStorage.setItem(pageTabKey, pageStorageKey);
localStorage.setItem(pageTabKey, pageStorageKey);
sessionStorage.setItem(pageStorageKey, '#page-features');
localStorage.setItem(pageStorageKey, '#page-features');
sessionStorage.setItem(modalTabKey, modalStorageKey);
localStorage.setItem(modalTabKey, modalStorageKey);
sessionStorage.setItem(modalStorageKey, '#modal-features');
localStorage.setItem(modalStorageKey, '#modal-features');

Mautic.rememberActiveTabs();

const activeTabs = {
    modal: mQuery('#modal-tab-persistence-fixture .nav-tabs a.active').attr('href'),
    page: mQuery('#page-tab-persistence-fixture .nav-tabs a.active').attr('href')
};

mQuery('#page-tab-persistence-fixture, #modal-tab-persistence-fixture').remove();
[pageTabKey, modalTabKey, pageStorageKey, modalStorageKey].forEach(function(key) {
    sessionStorage.removeItem(key);
    localStorage.removeItem(key);
});

return activeTabs;
JS
        );

        $I->assertSame('#modal-details', $activeTabs['modal']);
        $I->assertSame('#page-features', $activeTabs['page']);
    }
}
