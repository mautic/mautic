<?php

namespace Mautic\DynamicContentBundle\Tests\Validator\Constraints;

use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\DynamicContentBundle\Validator\Constraints\SlotNameType;
use Mautic\DynamicContentBundle\Validator\Constraints\SlotNameTypeValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SlotNameTypeValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var DynamicContentModel|MockObject
     */
    private $dynamicContentModel;

    protected function createValidator(): SlotNameTypeValidator
    {
        $this->dynamicContentModel = $this->createMock(DynamicContentModel::class);

        return new SlotNameTypeValidator($this->dynamicContentModel);
    }

    public function testValidSlotNameType(): void
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setSlotName('slot1');
        $dynamicContent->setType('html');
        $dynamicContent->setIsCampaignBased(false);

        $existingContent = new DynamicContent();
        $existingContent->setSlotName('slot1');
        $existingContent->setType('html');
        $dynamicContent->setIsCampaignBased(false);

        $this->dynamicContentModel->method('checkEntityBySlotName')->willReturn(false);

        $this->validator->validate($dynamicContent, new SlotNameType());

        $this->assertNoViolation();
    }

    public function testInvalidSlotNameType(): void
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setSlotName('slot1');
        $dynamicContent->setType('text');
        $dynamicContent->setIsCampaignBased(false);

        $existingContent = new DynamicContent();
        $existingContent->setSlotName('slot1');
        $existingContent->setType('html');
        $dynamicContent->setIsCampaignBased(false);

        $this->dynamicContentModel->method('checkEntityBySlotName')->willReturn(true);

        $constraint = new SlotNameType();
        $this->validator->validate($dynamicContent, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.type')
            ->assertRaised();
    }
}
