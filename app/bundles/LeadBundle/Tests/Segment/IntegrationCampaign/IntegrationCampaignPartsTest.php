<?php

namespace Mautic\LeadBundle\Tests\Segment\IntegrationCampaign;

use Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts;

#[\PHPUnit\Framework\Attributes\CoversClass(IntegrationCampaignParts::class)]
class IntegrationCampaignPartsTest extends \PHPUnit\Framework\TestCase
{
    public function testConnectwise(): void
    {
        $field             = 'Connectwise::283';
        $doNotContactParts = new IntegrationCampaignParts($field);

        $this->assertSame('Connectwise', $doNotContactParts->getIntegrationName());
        $this->assertSame('283', $doNotContactParts->getCampaignId());
    }

    public function testSalesforceExplicit(): void
    {
        $field             = 'Salesforce::22';
        $doNotContactParts = new IntegrationCampaignParts($field);

        $this->assertSame('Salesforce', $doNotContactParts->getIntegrationName());
        $this->assertSame('22', $doNotContactParts->getCampaignId());
    }

    public function testSalesforceDefault(): void
    {
        $field             = '44';
        $doNotContactParts = new IntegrationCampaignParts($field);

        $this->assertSame('Salesforce', $doNotContactParts->getIntegrationName());
        $this->assertSame('44', $doNotContactParts->getCampaignId());
    }
}
