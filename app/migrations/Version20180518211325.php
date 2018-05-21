<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180518211325 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Force a last exit date on all manuallyRemoved = 1 but do so by campaign to leverage existing index
        $em           = $this->container->get('doctrine')->getManager();
        $campaignRepo = $em->getRepository(Campaign::class);

        /** @var \Doctrine\ORM\Internal\Hydration\IterableResult $campaigns */
        $campaigns = $campaignRepo->getEntities(['iterator_mode' => true]);

        $dateTime        = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateLastExisted = $dateTime->format('Y-m-d H:i:s');

        while (($next = $campaigns->next()) !== false) {
            // Key is ID and not 0
            $campaign = reset($next);

            $this->addSql("UPDATE {$this->prefix}campaign_leads SET date_last_exited = '$dateLastExisted' where campaign_id = {$campaign->getId()} and manually_removed = 1");
        }
    }
}
