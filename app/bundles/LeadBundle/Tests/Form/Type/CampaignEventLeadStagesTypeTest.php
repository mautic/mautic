<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadStagesType;
use Mautic\StageBundle\Form\Type\StageListType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;

final class CampaignEventLeadStagesTypeTest extends AbstractMauticTestCase
{
    private CampaignEventLeadStagesType $campaignEventLeadStagesType;

    /**
     * @var FormBuilderInterface&MockObject
     */
    private FormBuilderInterface $formBuilderInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaignEventLeadStagesType = new CampaignEventLeadStagesType();
        $this->formBuilderInterface        = $this->createMock(FormBuilderInterface::class);
    }

    public function testCampaignEventLeadStagesTypeFormIsProperlyBuilt(): void
    {
        $parameters = [
            'label'       => 'mautic.lead.lead.field.stage',
            'label_attr'  => ['class' => 'control-label'],
            'multiple'    => true,
            'required'    => false,
        ];

        $this->formBuilderInterface->expects($this->once())
            ->method('add')
            ->with(
                'stages',
                StageListType::class,
                $parameters
            );

        $this->campaignEventLeadStagesType->buildForm($this->formBuilderInterface, []);
    }

    public function testThatGetBlockPrefixReturnsAValue(): void
    {
        $blockPrefix = $this->campaignEventLeadStagesType->getBlockPrefix();
        $this->assertNotEmpty($blockPrefix);
        $this->assertSame('campaignevent_lead_stages', $blockPrefix);
    }
}
