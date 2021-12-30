<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class PublicControllerTest extends MauticMysqlTestCase
{
    public function testUnsubscribeAfterEmailWasDeleted(): void
    {
        // TODO create segment email with unsubscribe link

        // TODO delete semgent email

        // TODO replace idHashHere with the actual email hash
        $this->client->request('GET', '/email/unsubscribe/idHashHere');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        Assert::assertStringContainsString(
            'will no longer recieve emails from us.',
            $this->client->getResponse()->getContent()
        );
    }
}
