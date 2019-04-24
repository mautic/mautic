<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Report;

use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\UserBundle\Model\UserModel;

class FieldsBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLeadColumns()
    {
        $fieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldModel->expects($this->exactly(2)) //We have 2 asserts
            ->method('getLeadFields')
            ->with()
            ->willReturn($this->getFields());

        $fieldsBuilder = new FieldsBuilder($fieldModel, $listModel, $userModel);

        $expected = [
            'l.id' => [
                'label' => 'mautic.lead.report.contact_id',
                'type'  => 'int',
                'link'  => 'mautic_contact_action',
            ],
            'i.ip_address' => [
                'label' => 'mautic.core.ipaddress',
                'type'  => 'text',
            ],
            'l.date_identified' => [
                'label'          => 'mautic.lead.report.date_identified',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(l.date_identified)',
            ],
            'l.points' => [
                'label' => 'mautic.lead.points',
                'type'  => 'int',
            ],
            'l.owner_id' => [
                'label' => 'mautic.lead.report.owner_id',
                'type'  => 'int',
                'link'  => 'mautic_user_action',
            ],
            'u.first_name' => [
                'label' => 'mautic.lead.report.owner_firstname',
                'type'  => 'string',
            ],
            'u.last_name' => [
                'label' => 'mautic.lead.report.owner_lastname',
                'type'  => 'string',
            ],
            'x.title' => [
                'label' => 'Title',
                'type'  => 'string',
            ],
            'x.email' => [
                'label' => 'Email',
                'type'  => 'email',
            ],
            'x.mobile' => [
                'label' => 'Mobile',
                'type'  => 'string',
            ],
            'x.points' => [
                'label' => 'Points',
                'type'  => 'float',
            ],
            'x.date' => [
                'label' => 'Date',
                'type'  => 'date',
            ],
            'x.web' => [
                'label' => 'Website',
                'type'  => 'url',
            ],
        ];

        $columns = $fieldsBuilder->getLeadFieldsColumns('x');
        $this->assertSame($expected, $columns);

        $columns = $fieldsBuilder->getLeadFieldsColumns('x.');
        $this->assertSame($expected, $columns);
    }

    public function testGetLeadFilter()
    {
        $fieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldModel->expects($this->once())
        ->method('getLeadFields')
            ->with()
            ->willReturn($this->getFields());

        $userSegments = [
            [
                'id'    => 1,
                'name'  => 'United States',
                'alias' => 'us',
            ],
            [
                'id'    => 2,
                'name'  => 'Segment with 3 filters',
                'alias' => 'segment-with-3-filters',
            ],
            [
                'id'    => 3,
                'name'  => 'Segment with 3 filters',
                'alias' => 'segment-with-3-filters1',
            ],
        ];

        $listModel->expects($this->once())
            ->method('getUserLists')
            ->with()
            ->willReturn($userSegments);

        $users = ['John', 'Doe'];

        $userModel->expects($this->once())
            ->method('getUserList')
            ->with()
            ->willReturn($users);

        $fieldsBuilder = new FieldsBuilder($fieldModel, $listModel, $userModel);

        $expected = [
            'l.id' => [
                'label' => 'mautic.lead.report.contact_id',
                'type'  => 'int',
                'link'  => 'mautic_contact_action',
            ],
            'i.ip_address' => [
                'label' => 'mautic.core.ipaddress',
                'type'  => 'text',
            ],
            'l.date_identified' => [
                'label'          => 'mautic.lead.report.date_identified',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(l.date_identified)',
            ],
            'l.points' => [
                'label' => 'mautic.lead.points',
                'type'  => 'int',
            ],
            'l.owner_id' => [
                'label' => 'mautic.lead.report.owner_id',
                'type'  => 'int',
                'link'  => 'mautic_user_action',
            ],
            'u.first_name' => [
                'label' => 'mautic.lead.report.owner_firstname',
                'type'  => 'string',
            ],
            'u.last_name' => [
                'label' => 'mautic.lead.report.owner_lastname',
                'type'  => 'string',
            ],
            'x.title' => [
                'label' => 'Title',
                'type'  => 'string',
            ],
            'x.email' => [
                'label' => 'Email',
                'type'  => 'email',
            ],
            'x.mobile' => [
                'label' => 'Mobile',
                'type'  => 'string',
            ],
            'x.points' => [
                'label' => 'Points',
                'type'  => 'float',
            ],
            'x.date' => [
                'label' => 'Date',
                'type'  => 'date',
            ],
            'x.web' => [
                'label' => 'Website',
                'type'  => 'url',
            ],
            'segment.leadlist_id' => [
                'alias' => 'segment_id',
                'label' => 'mautic.core.filter.lists',
                'type'  => 'select',
                'list'  => [
                    1 => 'United States',
                    2 => 'Segment with 3 filters',
                    3 => 'Segment with 3 filters',
                ],
                'operators' => [
                    'eq' => 'mautic.core.operator.equals',
                ],
            ],
            'x.owner_id' => [
                'label' => 'mautic.lead.list.filter.owner',
                'type'  => 'select',
                'list'  => [
                    0 => 'John',
                    1 => 'Doe',
                ],
            ],
        ];

        $columns = $fieldsBuilder->getLeadFilter('x', 'segment');
        $this->assertSame($expected, $columns);
    }

    public function testGetCompanyColumns()
    {
        $fieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userModel = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldModel->expects($this->exactly(2)) //We have 2 asserts
        ->method('getCompanyFields')
            ->with()
            ->willReturn($this->getFields());

        $fieldsBuilder = new FieldsBuilder($fieldModel, $listModel, $userModel);

        $expected = [
            'comp.id' => [
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
            'comp.companyname' => [
                'label' => 'mautic.lead.report.company.company_name',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companycity' => [
                'label' => 'mautic.lead.report.company.company_city',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companystate' => [
                'label' => 'mautic.lead.report.company.company_state',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companycountry' => [
                'label' => 'mautic.lead.report.company.company_country',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companyindustry' => [
                'label' => 'mautic.lead.report.company.company_industry',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'x.title' => [
                'label' => 'Title',
                'type'  => 'string',
            ],
            'x.email' => [
                'label' => 'Email',
                'type'  => 'email',
            ],
            'x.mobile' => [
                'label' => 'Mobile',
                'type'  => 'string',
            ],
            'x.points' => [
                'label' => 'Points',
                'type'  => 'float',
            ],
            'x.date' => [
                'label' => 'Date',
                'type'  => 'date',
            ],
            'x.web' => [
                'label' => 'Website',
                'type'  => 'url',
            ],
        ];

        $columns = $fieldsBuilder->getCompanyFieldsColumns('x');
        $this->assertSame($expected, $columns);

        $columns = $fieldsBuilder->getCompanyFieldsColumns('x.');
        $this->assertSame($expected, $columns);
    }

    /**
     * @return array
     */
    private function getFields()
    {
        $titleField = new Field();
        $titleField->setLabel('Title');
        $titleField->setAlias('title');
        $titleField->setType('string');

        $emailField = new Field();
        $emailField->setLabel('Email');
        $emailField->setAlias('email');
        $emailField->setType('email');

        $mobileField = new Field();
        $mobileField->setLabel('Mobile');
        $mobileField->setAlias('mobile');
        $mobileField->setType('tel');

        $pointField = new Field();
        $pointField->setLabel('Points');
        $pointField->setAlias('points');
        $pointField->setType('number');

        $dateField = new Field();
        $dateField->setLabel('Date');
        $dateField->setAlias('date');
        $dateField->setType('date');

        $webField = new Field();
        $webField->setLabel('Website');
        $webField->setAlias('web');
        $webField->setType('url');

        return [
            $titleField,
            $emailField,
            $mobileField,
            $pointField,
            $dateField,
            $webField,
        ];
    }
}
