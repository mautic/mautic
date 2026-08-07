<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class LeadCompanyControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['contact_allow_multiple_companies']   = 0;
        parent::setUp();
    }

    public function testSimpleCompanyFeature(): void
    {
        $crawler     = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, 's/contacts/new/');
        $multiple    = $crawler->filterXPath('//*[@id="lead_companies"]')->attr('multiple');
        $this->assertNull($multiple);
    }
}
