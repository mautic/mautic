<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\DoNotContact;

class LoadDncData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $dnc = new DoNotContact();
        $dnc->setChannel('sms');
        $dnc->setReason(DoNotContact::MANUAL);
        $dnc->setDateAdded(new \DateTime());
        $dnc->setLead($this->getReference('lead-1'));

        $manager->persist($dnc);
        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 8;
    }
}
