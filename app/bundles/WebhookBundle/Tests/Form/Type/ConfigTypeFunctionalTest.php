<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class ConfigTypeFunctionalTest extends MauticMysqlTestCase
{
    public function testSendEmailDetailsToggleIsOnByDefault(): void
    {
        $crawler = $this->client->request('GET', '/s/config/edit');

        // Updated CSS selector based on the new ID
        $yesSpan = $crawler->filter('#config_webhookconfig_webhook_email_details_label > div > span');

        // Assert that exactly one such span exists
        Assert::assertCount(1, $yesSpan, 'The "Yes" span for "Send email details" toggle should exist.');

        // Assert that the text within the span is "Yes"
        Assert::assertSame('Yes', $yesSpan->text(), 'The "Send email details" toggle should be set to "Yes" by default.');
    }
}
