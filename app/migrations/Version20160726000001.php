<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\TranslationMigrationTrait;
use Mautic\CoreBundle\Doctrine\VariantMigrationTrait;

/**
 * Class Version20160726000001.
 */
class Version20160726000001 extends AbstractMauticMigration
{
    use TranslationMigrationTrait;
    use VariantMigrationTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addTranslationSchema($schema, 'emails');
        $this->addVariantSchema($schema, 'emails');

        $this->addTranslationSchema($schema, 'pages');
        $this->addVariantSchema($schema, 'pages');

        $this->addTranslationSchema($schema, 'dynamic_content');
        $this->addVariantSchema($schema, 'dynamic_content');
    }
}
