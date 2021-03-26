<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Field\DTO;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\InvalidObjectTypeException;
use Mautic\LeadBundle\Field\DTO\CustomFieldObject;

class CustomFieldObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testLeadObject(): void
    {
        $leadField = new LeadField();

        $customFieldObject = new CustomFieldObject($leadField);

        $this->assertSame('leads', $customFieldObject->getObject());
    }

    public function testCompanyObject(): void
    {
        $leadField = new LeadField();
        $leadField->setObject('company');

        $customFieldObject = new CustomFieldObject($leadField);

        $this->assertSame('companies', $customFieldObject->getObject());
    }

    public function testInvalidObject(): void
    {
        $leadField = new LeadField();
        $leadField->setObject('xxx');

        $this->expectException(InvalidObjectTypeException::class);
        $this->expectExceptionMessage('xxx has no associated object');

        new CustomFieldObject($leadField);
    }
}
