<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170323111702 extends AbstractMauticMigration
{
    /**
     * Holds tweet table name with prefix.
     *
     * @var string
     */
    protected $tableName;

    /**
     * An array of tweets with md5(tweet_text) as the keys.
     * Used to match duplicates.
     *
     * @var array
     */
    protected $tweetCache = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->tableName = sprintf('%s%s', $this->prefix, 'tweets');
    }

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->tableName)) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE {$this->tableName} (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, page_id INT DEFAULT NULL, asset_id INT DEFAULT NULL, is_published TINYINT(1) NOT NULL, date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', created_by INT DEFAULT NULL, created_by_user VARCHAR(255) DEFAULT NULL, date_modified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', modified_by INT DEFAULT NULL, modified_by_user VARCHAR(255) DEFAULT NULL, checked_out DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', checked_out_by INT DEFAULT NULL, checked_out_by_user VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, media_id VARCHAR(255) DEFAULT NULL, media_path VARCHAR(255) DEFAULT NULL, text VARCHAR(255) NOT NULL, sent_count INT DEFAULT NULL, favorite_count INT DEFAULT NULL, retweet_count INT DEFAULT NULL, lang VARCHAR(255) DEFAULT NULL, INDEX tweet_text_index (text), INDEX favorite_count_index (favorite_count), INDEX sent_count_index (sent_count), INDEX retweet_count_index (retweet_count), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->addSql("CREATE TABLE {$this->prefix}tweet_stats (id INT AUTO_INCREMENT NOT NULL, tweet_id INT DEFAULT NULL, lead_id INT DEFAULT NULL, twitter_tweet_id VARCHAR(255) DEFAULT NULL, handle VARCHAR(255) NOT NULL, date_sent DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', is_failed TINYINT(1) DEFAULT NULL, retry_count INT DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, source_id INT DEFAULT NULL, favorite_count INT DEFAULT NULL, retweet_count INT DEFAULT NULL, response_details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', INDEX stat_tweet_search (tweet_id, lead_id), INDEX stat_tweet_search2 (lead_id, tweet_id), INDEX stat_tweet_failed_search (is_failed), INDEX stat_tweet_source_search (source, source_id), INDEX favorite_count_index (favorite_count), INDEX retweet_count_index (retweet_count), INDEX tweet_date_sent (date_sent), INDEX twitter_tweet_id_index (twitter_tweet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->addSql("ALTER TABLE {$this->tableName} ADD CONSTRAINT ".$this->generatePropertyName('tweets', 'fk', ['category_id'])." FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->tableName} ADD CONSTRAINT ".$this->generatePropertyName('tweets', 'fk', ['page_id'])." FOREIGN KEY (page_id) REFERENCES {$this->prefix}pages (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->tableName} ADD CONSTRAINT ".$this->generatePropertyName('tweets', 'fk', ['asset_id'])." FOREIGN KEY (asset_id) REFERENCES {$this->prefix}assets (id) ON DELETE SET NULL");

        $this->addSql("ALTER TABLE {$this->prefix}tweet_stats ADD CONSTRAINT ".$this->generatePropertyName('tweet_stats', 'fk', ['tweet_id'])." FOREIGN KEY (tweet_id) REFERENCES {$this->prefix}tweets (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}tweet_stats ADD CONSTRAINT ".$this->generatePropertyName('tweet_stats', 'fk', ['lead_id'])." FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL");

        $this->addSql('CREATE INDEX '.$this->generatePropertyName('tweets', 'idx', ['category_id']).' ON '.$this->tableName.' (category_id)');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('tweets', 'idx', ['page_id']).' ON '.$this->tableName.' (page_id)');
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('tweets', 'idx', ['asset_id']).' ON '.$this->tableName.' (asset_id)');
    }

    /**
     * Create tweet entities from the channel properties.
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $qb     = $this->connection->createQueryBuilder();
        $logger = $this->container->get('monolog.logger.mautic');
        $start  = 0;
        $batch  = 500;

        // Migrate tweets stored in Marketing Message params
        $qb->select('mc.id, mc.properties, mc.message_id, m.is_published, m.date_added, m.created_by, m.created_by_user')
            ->from($this->prefix.'message_channels', 'mc')
            ->leftJoin('mc', $this->prefix.'messages', 'm', 'm.id = mc.message_id')
            ->where('mc.channel = :tweet')
            ->andWhere('mc.properties <> :emptyTweet')
            ->andWhere('mc.properties <> :emptyProps')
            ->setParameter('emptyTweet', '{"tweet_text":null,"asset_link":null,"page_link":null}')
            ->setParameter('emptyProps', '[]')
            ->setParameter('tweet', 'tweet')
            ->setMaxResults($batch);

        while ($results = $qb->execute()->fetchAll()) {

            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                $row['properties']  = json_decode($row['properties'], true);
                $row['description'] = 'Migrated from marketing message ('.$row['message_id'].') channel ('.$row['id'].')';
                $tweetData          = $this->buildTweetData($row);
                if (!$tweetId = $this->getIdFromCache($tweetData['text'])) {
                    $this->connection->insert($this->tableName, $tweetData);

                    $tweetId = $this->connection->lastInsertId();
                    $this->addToCache($tweetData['text'], $tweetId);
                }

                $this->connection->update(
                    $this->prefix.'message_channels',
                    [
                        'channel_id' => $tweetId,
                    ],
                    [
                        'id' => $row['id'],
                    ]
                );
            }

            try {
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();

                $logger->addError($e->getMessage(), ['exception' => $e]);
            }

            // Increase the start
            $start += $batch;
            $qb->setFirstResult($start);
        }

        // Migrate tweets stored in campaign event params
        $qb    = $this->connection->createQueryBuilder();
        $start = 0;

        $qb->select('ce.id, ce.properties, ce.campaign_id, c.is_published, c.date_added, c.created_by, c.created_by_user')
            ->from($this->prefix.'campaign_events', 'ce')
            ->leftJoin('ce', $this->prefix.'campaigns', 'c', 'c.id = ce.campaign_id')
            ->andWhere('ce.channel = :tweet')
            ->setParameter('tweet', 'social.tweet')
            ->setMaxResults($batch);

        while ($results = $qb->execute()->fetchAll()) {

            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                $row['properties']  = unserialize($row['properties']);
                $row['description'] = 'Migrated from campaign ('.$row['campaign_id'].') event ('.$row['id'].')';
                $tweetData          = $this->buildTweetData($row);

                if (!$tweetId = $this->getIdFromCache($tweetData['text'])) {
                    $this->connection->insert($this->tableName, $tweetData);

                    $tweetId = $this->connection->lastInsertId();
                    $this->addToCache($tweetData['text'], $tweetId);
                }

                $this->connection->update(
                    $this->prefix.'campaign_events',
                    [
                        'channel_id' => $tweetId,
                    ],
                    [
                        'id' => $row['id'],
                    ]
                );
            }

            try {
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();

                $logger->addError($e->getMessage(), ['exception' => $e]);
            }

            // Increase the start
            $start += $batch;
            $qb->setFirstResult($start);
        }
    }

    /**
     * Generates tweet name from the tweet text.
     *
     * @param array $properties
     *
     * @return string
     */
    protected function generateTweetName(array $properties)
    {
        $name = isset($properties['tweet_text']) ? str_replace(PHP_EOL, '', $properties['tweet_text']) : 'Tweet';

        return (strlen($name) > 53) ? substr($name, 0, 50).'...' : $name;
    }

    /**
     * Builds the tweet data to be stored from the channel/event rows.
     *
     * @param array $row
     *
     * @return array
     */
    protected function buildTweetData(array $row)
    {
        $now        = (new \DateTime())->format('Y-m-d H:i:s');
        $properties = $row['properties'];

        return [
            'text'            => isset($properties['tweet_text']) ? $properties['tweet_text'] : '',
            'name'            => $this->generateTweetName($properties),
            'asset_id'        => isset($properties['asset_link']) ? $properties['asset_link'] : null,
            'page_id'         => isset($properties['page_link']) ? $properties['page_link'] : null,
            'is_published'    => (bool) $row['is_published'],
            'date_added'      => !empty($row['date_added']) ? $row['date_added'] : $now,
            'description'     => !empty($row['description']) ? $row['description'] : 'Created by migration',
            'created_by'      => (int) $row['created_by'],
            'created_by_user' => $row['created_by_user'],
            'lang'            => 'en',
        ];
    }

    /**
     * Builds the tweet cache so we could join duplicated tweets.
     *
     * @param string $text
     * @param int    $id
     */
    protected function addToCache($text, $id)
    {
        $this->tweetCache[md5($text)] = $id;
    }

    /**
     * Check tweetCache if the tweet exists. If so, returns tweet ID, if not false.
     *
     * @param string $text
     *
     * @return int|false
     */
    protected function getIdFromCache($text)
    {
        $hash = md5($text);

        if (isset($this->tweetCache[$hash])) {
            return $this->tweetCache[$hash];
        }

        return false;
    }
}
