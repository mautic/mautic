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

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;

class CompanyModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Ensure that an array value is flattened before saving
     *
     * @covers \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     */
    public function testArrayValueIsFlattenedBeforeSave()
    {
        /** @var CompanyModel $companyModel */
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $company = new Company();
        $company->setFields(
            [
                'core' => [
                    'multiselect' => [
                        'type'  => 'multiselect',
                        'alias' => 'multiselect',
                        'value' => 'abc|123',
                    ],
                ],
            ]
        );

        $companyModel->setFieldValues($company, ['multiselect' => ['abc', 'def']]);

        $updatedFields = $company->getUpdatedFields();

        $this->assertEquals(
            [
                'multiselect' => 'abc|def',
            ],
            $updatedFields
        );
    }
}
