<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Form\Type\CampaignConditionLeadPageHitType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;

final class CampaignConditionLeadPageHitTypeTest extends AbstractMauticTestCase
{
    private $campaignConditionPageHitType;
    /** @var FormBuilderInterface&MockObject */
    private FormBuilderInterface $formBuilderInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaignConditionPageHitType = new CampaignConditionLeadPageHitType();
        $this->formBuilderInterface         = $this->createMock(FormBuilderInterface::class);
    }

    public function testCampaignConditionPageHitTypeFormIsProperlyBuilt(): void
    {
        $this->formBuilderInterface->expects($this->exactly(4))
            ->method('add');

        $this->campaignConditionPageHitType->buildForm($this->formBuilderInterface, []);
    }

    public function testThatGetBlockPrefixReturnsAValue(): void
    {
        $blockPrefix = $this->campaignConditionPageHitType->getBlockPrefix();
        $this->assertNotEmpty($blockPrefix);
        $this->assertSame('campaigncondition_lead_pageHit', $blockPrefix);
    }
}
