<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Model\EmailModel;

class EmailModelBuildUrlTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://foo.bar.com';
        parent::setUp();
    }

    public function testSiteUrlAlwaysTakesPrecedenceWhenBuildingUrls(): void
    {
        /** @var EmailModel $emailModel */
        $emailModel = static::getContainer()->get('mautic.email.model.email');
        $idHash     = uniqid();
        $url        = $emailModel->buildUrl('mautic_email_unsubscribe', ['idHash' => $idHash]);

        self::assertSame('https://foo.bar.com/email/unsubscribe/'.$idHash, $url);
    }
}
