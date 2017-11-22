<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Symfony\Component\Translation\TranslatorInterface;

class CompanyReportDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->method('trans')
            ->willReturnCallback(
                function ($key) {
                    return $key;
                }
            );
    }

    /**
     * @covers \Mautic\LeadBundle\Model\CompanyReportData::getCompanyData
     */
    public function testGetCompanyData()
    {
        $fieldModelMock = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $field1 = new Field();
        $field1->setType('boolean');
        $field1->setAlias('boolField');
        $field1->setLabel('boolFieldLabel');

        $field2 = new Field();
        $field2->setType('email');
        $field2->setAlias('emailField');
        $field2->setLabel('emailFieldLabel');

        $fields = [
            $field1,
            $field2,
        ];

        $fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn($fields);

        $companyReportData = new CompanyReportData($fieldModelMock, $this->translator);

        $result = $companyReportData->getCompanyData();

        $expected = [
            'comp.id' => [
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
            'companies_lead.is_primary' => [
                'label' => 'mautic.lead.report.company.is_primary',
                'type'  => 'bool',
            ],
            'comp.boolField' => [
                'label' => 'mautic.report.field.company.label',
                'type'  => 'bool',
            ],
            'comp.emailField' => [
                'label' => 'mautic.report.field.company.label',
                'type'  => 'email',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * @covers \Mautic\LeadBundle\Model\CompanyReportData::eventHasCompanyColumns
     */
    public function testEventHasCompanyColumns()
    {
        $fieldModelMock = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock->expects($this->once())
            ->method('hasColumn')
            ->with('comp.id')
            ->willReturn(true);

        $companyReportData = new CompanyReportData($fieldModelMock, $this->translator);

        $result = $companyReportData->eventHasCompanyColumns($eventMock);

        $this->assertTrue($result);
    }

    /**
     * @covers \Mautic\LeadBundle\Model\CompanyReportData::eventHasCompanyColumns
     */
    public function testEventDoesNotHaveCompanyColumns()
    {
        $fieldModelMock = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock->expects($this->any())
            ->method('hasColumn')
            ->willReturn(false);

        $companyReportData = new CompanyReportData($fieldModelMock, $this->translator);

        $result = $companyReportData->eventHasCompanyColumns($eventMock);

        $this->assertFalse($result);
    }
}
