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
        $this->addSql("CREATE TABLE {$this->tableName} (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, page_id INT DEFAULT NULL, asset_id INT DEFAULT NULL, is_published TINYINT(1) NOT NULL, date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', created_by INT DEFAULT NULL, created_by_user VARCHAR(255) DEFAULT NULL, date_modified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', modified_by INT DEFAULT NULL, modified_by_user VARCHAR(255) DEFAULT NULL, checked_out DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', checked_out_by INT DEFAULT NULL, checked_out_by_user VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, tweet_id VARCHAR(255) DEFAULT NULL, media_id VARCHAR(255) DEFAULT NULL, media_path VARCHAR(255) DEFAULT NULL, text VARCHAR(255) NOT NULL, date_tweeted DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', favorite_count INT NOT NULL, retweet_count INT NOT NULL, lang VARCHAR(255) NOT NULL, INDEX tweet_id_index (tweet_id), INDEX tweet_text_index (text), INDEX favorite_count_index (favorite_count), INDEX retweet_count_index (retweet_count), INDEX date_tweeted_index (date_tweeted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->addSql("ALTER TABLE {$this->tableName} ADD CONSTRAINT ".$this->generatePropertyName('tweets', 'fk', ['category_id'])." FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->tableName} ADD CONSTRAINT ".$this->generatePropertyName('tweets', 'fk', ['page_id'])." FOREIGN KEY (page_id) REFERENCES {$this->prefix}pages (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->tableName} ADD CONSTRAINT ".$this->generatePropertyName('tweets', 'fk', ['asset_id'])." FOREIGN KEY (asset_id) REFERENCES {$this->prefix}assets (id) ON DELETE SET NULL");

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
        $qb                  = $this->connection->createQueryBuilder();
        $logger              = $this->container->get('monolog.logger.mautic');
        $tweetPropDefaultVal = '{"tweet_text":null,"asset_link":null,"page_link":null}';
        $start               = 0;

        $qb->select('mc.id, mc.properties')
           ->from($this->prefix.'message_channels', 'mc')
           ->where('mc.properties != :emptyTweet')
           ->andWhere('mc.channel = :tweet')
           ->setParameter('emptyTweet', $tweetPropDefaultVal)
           ->setParameter('tweet', 'tweet')
           ->setMaxResults(500);

        while ($results = $qb->execute()->fetchAll()) {

            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                $properties = json_decode($row['properties'], true);

                // Generate the tweet name from the tweet text
                $name = isset($properties['tweet_text']) ? str_replace(PHP_EOL, '', $properties['tweet_text']) : 'Tweet';
                $name = (strlen($name) > 53) ? substr($name, 0, 50).'...' : $name;

                $this->connection->insert(
                    $this->tableName,
                    [
                        'text'     => isset($properties['tweet_text']) ? $properties['tweet_text'] : '',
                        'name'     => $name,
                        'asset_id' => isset($properties['asset_link']) ? $properties['asset_link'] : null,
                        'page_id'  => isset($properties['page_link']) ? $properties['page_link'] : null,
                    ]
                );

                $tweetId = $this->connection->lastInsertId();

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
            $start += 500;
            $qb->setFirstResult($start);
        }
    }
}
