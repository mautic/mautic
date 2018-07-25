<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\MauticWebTestCase;

class LeadFieldRepositoryTest extends MauticWebTestCase
{
    public function testGetPropertyByFieldForLead()
    {
        $leadFieldRepo    = $this->em->getRepository('MauticLeadBundle:LeadField');
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->any())->method('join')->willReturn('');
        $this->assertEquals('l.email', $leadFieldRepo->getPropertyByField('email', $queryBuilderMock));
    }

    public function testGetPropertyByFieldForCompany()
    {
        $leadFieldRepo    = $this->em->getRepository('MauticLeadBundle:LeadField');
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->any())->method('join')->willReturn('');
        $this->assertEquals(
            'company.companyemail',
            $leadFieldRepo->getPropertyByField('company.companyemail', $queryBuilderMock)
        );
    }
}
