<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Helper\FieldAliasHelper;
use Mautic\LeadBundle\Model\FieldModel;

class FieldAliasHelperTest extends \PHPUnit_Framework_TestCase
{
    private $fieldModel;
    private $fieldRepository;
    private $helper;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldRepository = $this->createMock(LeadFieldRepository::class);
        $this->fieldModel      = $this->getMockBuilder(FieldModel::class)
            ->setMethods(['cleanAlias', 'getRepository'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldRepository->method('getAliases')->willReturn([
            'title',
            'firstname',
            'lastname',
        ]);

        $this->fieldModel->method('cleanAlias')->will($this->returnCallback(function () {
            return func_get_args()[0];
        }));

        $this->fieldModel->method('getRepository')->willReturn($this->fieldRepository);

        $this->helper = new FieldAliasHelper($this->fieldModel);
    }

    public function testDuplicatedAliasWithAliasSet()
    {
        $field = new LeadField();
        $field->setAlias('title');
        $field = $this->helper->makeAliasUnique($field);

        $this->assertEquals('title1', $field->getAlias());
    }

    public function testDuplicatedAliasWithAliasEmpty()
    {
        $field = new LeadField();
        $field->setName('title');
        $field = $this->helper->makeAliasUnique($field);

        $this->assertEquals('title1', $field->getAlias());
    }

    public function testUniqueAliasWithAliasEmpty()
    {
        $field = new LeadField();
        $field->setName('phone');
        $field = $this->helper->makeAliasUnique($field);

        $this->assertEquals('phone', $field->getAlias());
    }
}
