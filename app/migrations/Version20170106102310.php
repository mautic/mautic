<?php
/**
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see         http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170106102310 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'campaign_events');
        if ($table->hasColumn('channel')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE {$this->prefix}campaign_events
  ADD channel VARCHAR(255) DEFAULT NULL, 
  ADD channel_id INTEGER DEFAULT NULL,
  ADD INDEX {$this->prefix}campaign_event_channel (channel, channel_id),
  DROP INDEX {$this->prefix}campaign_event_type_search,
  ADD INDEX {$this->prefix}campaign_event_search (`type`, event_type),
  DROP INDEX {$this->prefix}event_type,
  ADD INDEX {$this->prefix}campaign_event_type (event_type)
SQL;
        $this->addSql($sql);

        $channel = '';
        if (!$schema->getTable($this->prefix.'campaign_lead_event_log')->hasColumn('channel')) {
            $channel = 'ADD channel VARCHAR(255) DEFAULT NULL, ADD channel_id INT DEFAULT NULL,';
        }

        $sql = <<<SQL
ALTER TABLE {$this->prefix}campaign_lead_event_log
  $channel
  DROP PRIMARY KEY,
  ADD id INT AUTO_INCREMENT NOT NULL,
  ADD PRIMARY KEY(id),
  ADD rotation INTEGER NOT NULL DEFAULT 1,
  ADD INDEX {$this->prefix}campaign_log_channel (channel, channel_id, lead_id),
  ADD UNIQUE INDEX {$this->prefix}campaign_rotation (event_id, lead_id, rotation),
  DROP INDEX {$this->prefix}campaign_leads,
  ADD INDEX {$this->prefix}campaign_leads (campaign_id, lead_id, rotation),
  DROP INDEX {$this->prefix}event_upcoming_search,
  ADD INDEX {$this->prefix}campaign_event_upcoming_search (is_scheduled, lead_id)
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
 ALTER TABLE {$this->prefix}campaign_leads 
  ADD rotation INTEGER NOT NULL DEFAULT 1,
  ADD date_last_exited DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  ADD INDEX {$this->prefix}campaign_leads_date_exited (date_last_exited),
  DROP INDEX {$this->prefix}campaign_leads,
  ADD INDEX {$this->prefix}campaign_leads (campaign_id, manually_removed, lead_id, rotation)
SQL;
        $this->addSql($sql);
    }

    /**
     * Update all the channel events with their assigned channels.
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        /** @var CampaignModel $campaignModel */
        $campaignModel = $this->container->get('mautic.campaign.model.campaign');
        $eventSettings = $campaignModel->getEvents();

        $eventsWithChannels = [];
        foreach ($eventSettings as $type => $events) {
            foreach ($events as $eventType => $settings) {
                if (!empty($settings['channel'])) {
                    $eventsWithChannels[$eventType] = [
                        'channel'        => $settings['channel'],
                        'channelIdField' => null,
                    ];

                    if (!empty($settings['channelIdField'])) {
                        $eventsWithChannels[$eventType]['channelIdField'] = $settings['channelIdField'];
                    }
                }
            }
        }

        // Let's update
        $logger = $this->container->get('monolog.logger.mautic');
        $qb     = $this->connection->createQueryBuilder();

        $qb->select('e.id, e.type, e.properties')
           ->from($this->prefix.'campaign_events', 'e')
           ->where(
               $qb->expr()->in('e.type', array_map([$qb->expr(), 'literal'], array_keys($eventsWithChannels)))
           )
           ->setMaxResults(500);

        $start = 0;
        while ($results = $qb->execute()->fetchAll()) {
            $eventChannels = [];

            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                $channelId  = null;
                $eventType  = $row['type'];
                $properties = unserialize($row['properties']);
                $field      = !empty($eventsWithChannels[$eventType]['channelIdField']) ? $eventsWithChannels[$eventType]['channelIdField'] : null;
                if ($field && isset($properties[$field])) {
                    if (is_array($properties[$field])) {
                        if (count($properties[$field]) === 1) {
                            $channelId = $properties[$field][0];
                        }
                    } elseif (!empty($properties[$field])) {
                        $channelId = $properties[$field];
                    }
                }

                $eventChannels[$row['id']] = [
                    'channel'    => $eventsWithChannels[$eventType]['channel'],
                    'channel_id' => (is_numeric($channelId)) ? $channelId : null,
                ];

                $this->connection->update(
                    MAUTIC_TABLE_PREFIX.'campaign_events',
                    $eventChannels[$row['id']],
                    [
                        'id' => $row['id'],
                    ]
                );
            }

            try {
                $this->connection->commit();

                // Update logs
                $this->connection->beginTransaction();
                foreach ($eventChannels as $id => $channel) {
                    $lqb = $this->connection->createQueryBuilder()
                                            ->update($this->prefix.'campaign_lead_event_log')
                                            ->set('channel', ':channel')
                                            ->set('channel_id', ':channel_id')
                                            ->setParameters($channel);
                    $lqb->where(
                        $lqb->expr()->andX(
                            $lqb->expr()->eq('event_id', $id),
                            $lqb->expr()->isNull('channel')
                        )
                    )->execute();
                }
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();

                $logger->addError($e->getMessage(), ['exception' => $e]);
            }

            // Increase the start
            $start += 500;
            $qb->setFirstResult($start);
        }
    }
}
