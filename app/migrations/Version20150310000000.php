<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Schema update for Version 1.0.0-rc4 to 1.0.0
 *
 * Class Version20150310000000
 *
 * @package Mautic\Migrations
 */
class Version20150310000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix . 'plugins')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->connection->delete(MAUTIC_TABLE_PREFIX . 'addons', array('bundle' => 'MauticChatBundle'));

        $qb = $this->connection->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX . 'lead_fields')
            ->set('is_fixed', ':false')
            ->where(
                $qb->expr()->notIn('alias',
                    array_map(
                        function($v) use ($qb) {
                            return $qb->expr()->literal($v);
                        },
                        array(
                            'title',
                            'firstname',
                            'lastname',
                            'position',
                            'company',
                            'email',
                            'phone',
                            'mobile',
                            'address1',
                            'address2',
                            'country',
                            'city',
                            'state',
                            'zipcode'
                        )
                    )
                )
            )
            ->setParameter('false', false, 'boolean')
            ->execute();
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        // see preUp
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        // see preUp
    }

    /**
     * @param Schema $schema
     */
    public function mssqlUp(Schema $schema)
    {
        // see preUp
    }
}