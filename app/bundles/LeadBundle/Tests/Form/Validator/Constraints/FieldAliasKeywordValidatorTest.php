<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Form\Validator\Constraints;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeyword;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FieldAliasKeywordValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $listModelMock;
    private $executionContextMock;
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->listModelMock = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listModelMock->method('getChoiceFields')
            ->willReturn(
                [
                    'lead' => [
                        'date_added' => [
                            'label'      => 'mautic.core.date.added',
                            'properties' => ['type' => 'date'],
                            'operators'  => 'default',
                            'object'     => 'lead',
                        ],
                        'date_identified' => [
                            'label'      => 'mautic.lead.list.filter.date_identified',
                            'properties' => ['type' => 'date'],
                            'operators'  => 'default',
                            'object'     => 'lead',
                        ],
                    ],
                ]
            );

        $this->executionContextMock = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new FieldAliasKeywordValidator($this->listModelMock);
        $this->validator->initialize($this->executionContextMock);
    }

    public function testValidationFailure()
    {
        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('date_added');

        $this->executionContextMock->expects($this->once())->method('addViolation')->with('mautic.lead.field.keyword.invalid');

        $this->validator->validate($field, new FieldAliasKeyword());
    }

    public function testValidationSuccess()
    {
        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('not_keyword');

        $this->executionContextMock->expects($this->never())->method('addViolation');

        $this->validator->validate($field, new FieldAliasKeyword());
    }
}
