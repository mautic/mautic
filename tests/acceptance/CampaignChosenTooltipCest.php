<?php

declare(strict_types=1);

namespace Acceptance;

final class CampaignChosenTooltipCest
{
    public function campaignTooltipsCanBeDestroyedRepeatedlyWithoutJavaScriptError(\AcceptanceTester $I): void
    {
        $I->amOnPage('/s/login');
        $I->waitForJS("return window.Mautic && typeof window.Mautic.campaignBuilderUpdateEventListTooltips === 'function';", \AcceptanceTester::TIMEOUT);

        /** @var array{animation: bool|null, tooltipElements: int, trackedTooltips: int} $setup */
        $setup = $I->executeJS(<<<'JS'
const $fixture = mQuery('<div id="campaign-tooltip-regression-fixture"></div>').appendTo(document.body);
const $select = mQuery(
    '<select id="CampaignTooltipRegressionSelect">'
    + '<option value=""></option>'
    + '<option id="campaignTooltipRegressionOption" '
    + 'class="option_campaignTooltipRegressionOption" '
    + 'title="Tooltip content" value="email.send">Send email</option>'
    + '</select>'
).appendTo($fixture);

$select.chosen({search_contains: true});
$select.trigger('chosen:open');
Mautic.campaignBuilderUpdateEventListTooltips($select.attr('id'), false);

const tooltips = $select.data('tooltips');
const $tooltip = tooltips[0];
const tooltip = $tooltip.data('bs.tooltip');
const originalHide = tooltip.hide;

window.campaignTooltipRegressionError = null;
window.campaignTooltipRegressionTeardownState = {scheduled: 0, completed: 0};
window.campaignTooltipRegressionErrorHandler = function(event) {
    window.campaignTooltipRegressionError = event.error instanceof Error
        ? event.error.message
        : event.message;
    event.preventDefault();
};
window.addEventListener('error', window.campaignTooltipRegressionErrorHandler);

tooltip.hide = function(callback) {
    const state = window.campaignTooltipRegressionTeardownState;
    state.scheduled++;

    return originalHide.call(this, function() {
        try {
            if (typeof callback === 'function') {
                callback();
            }
        } finally {
            state.completed++;
        }
    });
};

$tooltip.tooltip('show');
$select.data('tooltips', [$tooltip, $tooltip]);
Mautic.campaignBuilderUpdateEventListTooltips($select.attr('id'), true);

return {
    animation: tooltip ? tooltip.options.animation : null,
    tooltipElements: $tooltip.length,
    trackedTooltips: $select.data('tooltips').length,
};
JS
        );

        $I->waitForJS(<<<'JS'
const state = window.campaignTooltipRegressionTeardownState;

return state && state.scheduled > 0 && state.completed === state.scheduled;
JS, \AcceptanceTester::TIMEOUT);

        /** @var string|null $error */
        $error = $I->executeJS(<<<'JS'
const error = window.campaignTooltipRegressionError;
window.removeEventListener('error', window.campaignTooltipRegressionErrorHandler);
delete window.campaignTooltipRegressionError;
delete window.campaignTooltipRegressionErrorHandler;
delete window.campaignTooltipRegressionTeardownState;
mQuery('#campaign-tooltip-regression-fixture').remove();

return error;
JS
        );

        $I->assertSame(1, $setup['tooltipElements']);
        $I->assertFalse($setup['animation']);
        $I->assertSame(0, $setup['trackedTooltips']);
        $I->assertNull($error);
    }
}
