<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Model\EmailModel;

final class EmailModelBuildUrlTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://foo.bar.com';
        parent::setUp();
    }

    public function testSiteUrlAlwaysTakesPrecedenceWhenBuildingUrls(): void
    {
        /** @var EmailModel $emailModel */
        $emailModel = self::getContainer()->get(EmailModel::class);
        $idHash     = uniqid();
        $url        = $emailModel->buildUrl('mautic_email_validate_email_form', ['action' => 'unsubscribe', 'secretHash' => 'somehash', 'idHash' => $idHash]);

        $this->assertSame('https://foo.bar.com/email/validate/unsubscribe/somehash/'.$idHash, $url);
    }
}
