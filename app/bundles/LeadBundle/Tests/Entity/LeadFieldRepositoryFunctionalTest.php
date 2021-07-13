<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class LeadFieldRepositoryFunctionalTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
        ]);
    }

    public function testCompareValueEqualsOperator()
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'firstname', 'John', 'eq'));
        $this->assertFalse($repository->compareValue(1, 'firstname', 'Jack', 'eq'));
    }

    public function testCompareValueNotEqualsOperator()
    {
        $lead = new Lead();
        $lead->setFirstname('Ada');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'firstname', 'Annie', 'neq'));
        $this->assertFalse($repository->compareValue(1, 'firstname', 'Ada', 'neq'));
    }

    public function testCompareValueEmptyOperator()
    {
        $lead = new Lead();
        $this->em->persist($lead);
        $lead->setFirstname('Ada');
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'lastname', null, 'empty'));
        $this->assertFalse($repository->compareValue(1, 'firstname', null, 'empty'));
    }

    public function testCompareValueNotEmptyOperator()
    {
        $lead = new Lead();
        $this->em->persist($lead);
        $lead->setFirstname('Ada');
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'firstname', null, 'notEmpty'));
        $this->assertFalse($repository->compareValue(1, 'lastname', null, 'notEmpty'));
    }

    public function testCompareValueStartsWithOperator()
    {
        $lead = new Lead();
        $this->em->persist($lead);
        $lead->setEmail('MaryWNevarez@armyspy.com');
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'email', 'Mary', 'startsWith'));
        $this->assertFalse($repository->compareValue(1, 'email', 'Unicorn', 'startsWith'));
    }

    public function testCompareValueEndWithOperator()
    {
        $lead = new Lead();
        $this->em->persist($lead);
        $lead->setEmail('MaryWNevarez@armyspy.com');
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'email', 'armyspy.com', 'endsWith'));
        $this->assertFalse($repository->compareValue(1, 'email', 'Unicorn', 'endsWith'));
    }

    public function testCompareValueContainsOperator()
    {
        $lead = new Lead();
        $this->em->persist($lead);
        $lead->setEmail('MaryWNevarez@armyspy.com');
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'email', 'Nevarez', 'contains'));
        $this->assertFalse($repository->compareValue(1, 'email', 'Unicorn', 'contains'));
    }

    public function testCompareValueInOperator()
    {
        $lead = new Lead();
        $lead->setCountry('United Kingdom');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'country', ['United Kingdom', 'South Africa'], 'in'));
        $this->assertFalse($repository->compareValue(1, 'country', ['Poland', 'Canada'], 'in'));
    }

    public function testCompareValueNotInOperator()
    {
        $lead = new Lead();
        $lead->setCountry('United Kingdom');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = $this->getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue(1, 'country', ['Australia', 'Poland'], 'notIn'));
        $this->assertFalse($repository->compareValue(1, 'country', ['United Kingdom'], 'notIn'));
    }
}
