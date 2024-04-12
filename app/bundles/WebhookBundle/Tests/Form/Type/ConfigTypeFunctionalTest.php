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

        // get "Yes" input for "Send email details" toggle
        $input = $crawler->filterXPath('//div[label[contains(text(), "Send email details")]]')
            ->filterXPath('//label[*[contains(text(), "Yes")]]')
            ->filter('input');

        Assert::assertCount(1, $input);
        Assert::assertSame('checked', $input->attr('checked'));
    }
}
