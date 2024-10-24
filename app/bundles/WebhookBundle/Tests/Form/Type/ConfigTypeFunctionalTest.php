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

        // Find the "Yes" span for "Send email details" toggle
        $yesSpan = $crawler->filterXPath('//div[label[contains(text(), "Send email details")]]')
            ->filterXPath('//span[contains(text(), "Yes")]')
            ->filter('span');

        Assert::assertCount(1, $yesSpan);
        Assert::assertSame('Yes', $yesSpan->text());
    }
}
