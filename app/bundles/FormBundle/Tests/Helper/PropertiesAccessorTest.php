<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Form\Helper;

use Mautic\FormBundle\Helper\PropertiesAccessor;
use Mautic\FormBundle\Model\FormModel;
use PHPUnit\Framework\MockObject\MockObject;

final class PropertiesAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormModel
     */
    private $formModel;

    /**
     * @var PropertiesAccessor
     */
    private $propertiesAccessor;

    protected function setUp()
    {
        $this->formModel          = $this->createMock(FormModel::class);
        $this->propertiesAccessor = new PropertiesAccessor(
            $this->formModel
        );
    }

    public function testGetPropertiesForCountryField()
    {
        $field = [
            'type'      => 'country',
            'leadField' => 'country',
        ];

        $this->formModel->expects($this->once())
            ->method('getContactFieldPropertiesList')
            ->with('country')
            ->willReturn(['some_props_here']);

        $this->assertSame(
            ['some_props_here'],
            $this->propertiesAccessor->getProperties($field)
        );
    }

    public function testGetPropertiesForSyncList()
    {
        $field = [
            'type'       => 'custom_select_a',
            'leadField'  => 'contact_field_a',
            'properties' => ['syncList' => true],
        ];

        $this->formModel->expects($this->once())
            ->method('getContactFieldPropertiesList')
            ->with('contact_field_a')
            ->willReturn(['some_props_here']);

        $this->assertSame(
            ['some_props_here'],
            $this->propertiesAccessor->getProperties($field)
        );
    }

    public function testGetPropertiesForTextField()
    {
        $field = [
            'type'       => 'custom_text_a',
            'leadField'  => 'contact_field_a',
            'properties' => ['syncList' => false],
        ];

        $this->formModel->expects($this->never())
            ->method('getContactFieldPropertiesList');

        $this->assertSame(
            [],
            $this->propertiesAccessor->getProperties($field)
        );
    }

    public function testGetPropertiesForListField()
    {
        $field = [
            'type'       => 'custom_select_a',
            'properties' => [
                'syncList' => false,
                'list'     => ['list' => ['option_a' => 'Option A']],
            ],
        ];

        $this->formModel->expects($this->never())
            ->method('getContactFieldPropertiesList');

        $this->assertSame(
            ['option_a' => 'Option A'],
            $this->propertiesAccessor->getProperties($field)
        );
    }

    public function testGetPropertiesForOptionlistField()
    {
        $field = [
            'type'       => 'custom_select_a',
            'properties' => [
                'syncList'   => false,
                'optionlist' => ['list' => ['option_a' => 'Option A']],
            ],
        ];

        $this->formModel->expects($this->never())
            ->method('getContactFieldPropertiesList');

        $this->assertSame(
            ['option_a' => 'Option A'],
            $this->propertiesAccessor->getProperties($field)
        );
    }

    public function testGetChoicesForWellFormattedChoices()
    {
        $options = ['choice_a' => 'Choice A'];

        $this->assertSame(
            $options,
            $this->propertiesAccessor->getChoices($options)
        );
    }

    public function testGetChoicesForPipeFormattedChoices()
    {
        $options = 'Choice A|Choice B';

        $this->assertSame(
            ['Choice A' => 'Choice A', 'Choice B' => 'Choice B'],
            $this->propertiesAccessor->getChoices($options)
        );
    }

    public function testGetChoicesForLabelValueArrayChoices()
    {
        $options = [
            [
                'label' => 'Choice A',
                'value' => 'Value A',
            ],
        ];

        $this->assertSame(
            ['Value A' => 'Choice A'],
            $this->propertiesAccessor->getChoices($options)
        );
    }
}
