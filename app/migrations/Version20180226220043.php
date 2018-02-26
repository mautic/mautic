<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PluginBundle\Entity\Integration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180226220043 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $integrationHelper    = $this->container->get('mautic.helper.integration');
        $pipedriveIntegration = $integrationHelper->getIntegrationObject('Pipedrive');
        if (!$this->isAuthorized()) {
            throw new SkipMigrationException('Schema includes this migration');
        }
        //$settings                 = $pipedriveIntegration->getIntegrationSettings()->getFeatureSettings();
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Please modify to your needs
    }
}
