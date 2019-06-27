<?php

/*
 * @package     Mautic
 * @copyright   2019 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20190627212742 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $repo = $this->factory->getModel('email')->getRepository();

        $emailsWithVariant = $repo->getAllEmailsWithVariant();

        foreach($emailsWithVariant as $email)  {
           $variantSettings = $email->getVariantSettings();
           if (array_key_exists('winnerCriteria', $variantSettings)) {
                continue;
           }

           $childSettings = $email->getVariantChildren()->first()->getVariantSettings();
           if (array_key_exists('winnerCriteria', $childSettings)) {
               $variantSettings['winnerCriteria'] = $childSettings['winnerCriteria'];
               $variantSettings['totalWeight'] = 100;
               $email->setVariantSettings($variantSettings);
               $repo->saveEntity($email, true);
           }
        }
    }
}
