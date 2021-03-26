<?php

declare(strict_types=1);

/*
 * @copyright   <year> Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200815153711 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $shouldRunMigration = true;

        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $oldAndNewValues = [
            'Braganca'                   => 'Bragança',
            'Colmbra'                    => 'Coimbra',
            'Ovora'                      => 'Évora',
            'Santarem'                   => 'Santarém',
            'Setubal'                    => 'Setúbal',
            'Regiao Autonoma dos Acores' => 'Região Autónoma dos Açores',
            'Regiao Autonoma da Madeira' => 'Região Autónoma da Madeira',
        ];

        foreach ($oldAndNewValues as $old => $new) {
            $this->addSql("UPDATE `{$this->prefix}leads` SET `state` = '{$new}' WHERE `state` = '{$old}'");
        }
    }
}
