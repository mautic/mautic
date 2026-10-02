<?php

declare(strict_types=1);

final class AssetBatchDownloadCest
{
    public function batchDownloadSubmitsPostWithCsrfToken(AcceptanceTester $tester): void
    {
        $tester->login();
        $tester->amOnPage('/s/assets');
        $tester->waitForElement('[data-mautic-batch-download]', 30);
        $tester->waitForElement('#assetTable thead [data-toggle="checkall"]', 30);
        $tester->click('#assetTable thead [data-toggle="checkall"]');
        $tester->waitForElementVisible('[data-mautic-batch-download]', 30);
        $tester->waitForJS("return typeof Mautic.batchAssetDownload === 'function';", 30);

        $submission = $tester->executeJS(<<<'JS'
            const trigger = document.querySelector('[data-mautic-batch-download]');
            const table = document.querySelector('#assetTable');

            if (!trigger || !table || typeof Mautic.batchAssetDownload !== 'function') {
                return null;
            }

            const expectedToken = trigger.getAttribute('data-csrf-token');
            const expectedAction = new URL(trigger.getAttribute('data-mautic-batch-download'), window.location.href).pathname;
            const expectedIds = Mautic.getCheckedListIds(trigger, true);
            let submission = null;

            HTMLFormElement.prototype.submit = function () {
                const ids = this.elements.namedItem('ids');
                const token = this.elements.namedItem('_token');

                submission = {
                    method: this.method.toUpperCase(),
                    action: new URL(this.action, window.location.href).pathname,
                    expectedAction,
                    ids: ids ? ids.value : null,
                    expectedIds: Array.isArray(expectedIds) ? expectedIds.join(',') : String(expectedIds),
                    token: token ? token.value : null,
                    expectedToken,
                    target: this.target
                };
            };

            trigger.click();

            return submission;
        JS);

        $tester->assertIsArray($submission);
        $tester->assertSame('POST', $submission['method']);
        $tester->assertSame($submission['expectedAction'], $submission['action']);
        $tester->assertSame($submission['expectedIds'], $submission['ids']);
        $tester->assertNotEmpty($submission['token']);
        $tester->assertSame($submission['expectedToken'], $submission['token']);
        $tester->assertNotEmpty($submission['target']);
    }
}
