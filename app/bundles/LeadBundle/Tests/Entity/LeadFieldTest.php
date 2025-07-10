<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\LeadField;

class LeadFieldTest extends \PHPUnit\Framework\TestCase
{
    public function testNewEntity(): void
    {
        $leadField = new LeadField();

        $this->assertTrue($leadField->isNew());
        $this->assertFalse($leadField->getColumnIsNotCreated());
    }

    public function testColumnNotCreatedForPublishedEntity(): void
    {
        $leadField = new LeadField();
        $leadField->setIsPublished(true);

        $this->assertTrue($leadField->getIsPublished());

        $leadField->setColumnIsNotCreated();

        $this->assertFalse($leadField->getIsPublished(), 'Entity cannot be published until column is not created');
        $this->assertTrue($leadField->getColumnIsNotCreated());

        $leadField->setColumnWasCreated();

        $this->assertTrue($leadField->getIsPublished());
        $this->assertFalse($leadField->getColumnIsNotCreated());
    }

    public function testColumnNotCreatedForUnpublishedEntity(): void
    {
        $leadField = new LeadField();
        $leadField->setIsPublished(false);

        $this->assertFalse($leadField->getIsPublished());

        $leadField->setColumnIsNotCreated();

        $this->assertFalse($leadField->getIsPublished());
        $this->assertTrue($leadField->getColumnIsNotCreated());

        $leadField->setColumnWasCreated();

        $this->assertFalse($leadField->getIsPublished());
        $this->assertFalse($leadField->getColumnIsNotCreated());
    }

    public function testEmailCannotBeUnpublished(): void
    {
        $leadField = new LeadField();
        $leadField->setIsPublished(true);

        $this->assertFalse($leadField->disablePublishChange());

        $leadField->setAlias('email');

        $this->assertTrue($leadField->disablePublishChange());
    }

    public function testCannotBeUnpublishedUntilColumnIsCreated(): void
    {
        $leadField = new LeadField();
        $leadField->setIsPublished(false);

        $this->assertFalse($leadField->disablePublishChange());

        $leadField->setColumnIsNotCreated();

        $this->assertTrue($leadField->disablePublishChange());

        $leadField->setColumnWasCreated();

        $this->assertFalse($leadField->disablePublishChange());
    }

    public function testClone(): void
    {
        $leadField = new LeadField();
        $leadField->setLabel('Test value for custom field 4');
        $leadField->setAlias('test_value_for_custom_field_4');

        $clonedField = clone $leadField;

        $this->assertEquals($leadField->getLabel(), $clonedField->getLabel());
        $this->assertEquals($leadField->getAlias(), $clonedField->getAlias());
        $this->assertEquals(0, $clonedField->getOrder());
        $this->assertTrue($clonedField->getIsCloned());
    }
}
