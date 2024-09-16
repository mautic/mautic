<?php

namespace Mautic\CampaignBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CampaignBundle\Entity\Campaign;

class CampaignData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $campaign = new Campaign();

        $campaign->setName('Campaign A');
        $campaign->setCanvasSettings([
            'nodes' => [
              0 => [
                'id'        => '148',
                'positionX' => '760',
                'positionY' => '155',
              ],
              1 => [
                'id'        => 'lists',
                'positionX' => '860',
                'positionY' => '50',
              ],
            ],
            'connections' => [
              0 => [
                'sourceId' => 'lists',
                'targetId' => '148',
                'anchors'  => [
                  'source' => 'leadsource',
                  'target' => 'top',
                ],
              ],
            ],
          ]
        );

        $manager->persist($campaign);
        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 0;
    }
}
