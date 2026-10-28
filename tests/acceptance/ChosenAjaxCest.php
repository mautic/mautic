<?php

declare(strict_types=1);

namespace Acceptance;

final class ChosenAjaxCest
{
    public function emptyAjaxResponseSynchronizesChosenWithTheSelect(\AcceptanceTester $I): void
    {
        $I->amOnPage('/s/login');
        $I->waitForJS("return window.mQuery && typeof window.mQuery.fn.ajaxChosen === 'function';", 10);

        $I->executeJS(<<<'JS'
window.chosenAjaxRegressionState = null;

const originalAjax = mQuery.ajax;
const $fixture = mQuery('<div id="chosen-ajax-regression-fixture"></div>').appendTo(document.body);
const $select = mQuery(
    '<select id="chosen-ajax-regression-select">'
    + '<option value="">Choose a translation</option>'
    + '<option value="stale">Stale translation</option>'
    + '</select>'
).appendTo($fixture);

mQuery.ajax = function (options) {
    setTimeout(function () {
        options.success([]);
    }, 0);

    return {abort: function () {}};
};

$select.ajaxChosen({
    afterTypeDelay: 0,
    minTermLength: 1,
    success: function () {
        const chosen = $select.data('chosen');
        window.chosenAjaxRegressionState = {
            nativeOptions: $select.find('option').length,
            nativeStaleOptions: $select.find('option[value="stale"]').length,
            noResults: chosen.search_results.find('li.no-results').length,
            parsedOptions: chosen.results_data.length,
            parsedStaleOptions: chosen.results_data.filter(function (option) {
                return option.value === 'stale';
            }).length,
            searchValue: chosen.search_field.val()
        };

        mQuery.ajax = originalAjax;
        $fixture.remove();
    }
}, function (data) {
    return data;
});

const $search = $select.next('.chosen-container').find('.chosen-search > input');
const event = mQuery.Event('keyup');
event.which = 65;
$search.val('missing translation').trigger(event);
JS
        );

        $I->waitForJS('return window.chosenAjaxRegressionState !== null;', 10);

        /** @var array{nativeOptions: int, nativeStaleOptions: int, noResults: int, parsedOptions: int, parsedStaleOptions: int, searchValue: string} $state */
        $state = $I->executeJS('return window.chosenAjaxRegressionState;');

        $I->assertSame(1, $state['nativeOptions']);
        $I->assertSame(1, $state['parsedOptions']);
        $I->assertSame(0, $state['nativeStaleOptions']);
        $I->assertSame(0, $state['parsedStaleOptions']);
        $I->assertSame(1, $state['noResults']);
        $I->assertSame('missing translation', $state['searchValue']);
    }
}
