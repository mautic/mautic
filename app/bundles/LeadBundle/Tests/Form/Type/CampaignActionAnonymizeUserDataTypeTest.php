<?php

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Form\Type\CampaignActionAnonymizeUserDataType;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignActionAnonymizeUserDataTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildForm(): void
    {
        $lead = $this->createMock(LeadField::class);
        $lead->expects($this->once())->method('getId')->willReturn(1);
        $lead->expects($this->once())->method('getLabel')->willReturn('email');

        $fieldsChoices = [
            $lead,
        ];

        $fieldModel       = $this->createMock(FieldModel::class);
        $fieldRepository  = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->expects($this->once())->method('findBy')->willReturn($fieldsChoices);
        $fieldModel->expects($this->once())->method('getRepository')->willReturn($fieldRepository);
        $builder    = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(3))->method('add');
        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel);
        $campaignActionAnonymizeUserDataType->buildForm($builder, []);
    }

    public function testGetBlockPrefix(): void
    {
        $fieldModel                          = $this->createMock(FieldModel::class);
        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel);
        $this->assertEquals('lead_action_anonymizeuserdata', $campaignActionAnonymizeUserDataType->getBlockPrefix());
    }
}
