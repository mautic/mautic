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
        $repo  = $this->container->get('mautic.email.repository.email');
        $model = $this->container->get('mautic.email.model.email');
        $em    = $this->container->get('doctrine')->getManager();

        $emailsWithVariantIterator = $repo->getEmailsWithVariantIterator();

        $batchSize = 20;
        $i         = 0;

        while (($emailArray = $emailsWithVariantIterator->next()) !== false) {
            $id = array_pop($emailArray)['id'];

            $email = $model->getEntity($id);

            $variantSettings = $email->getVariantSettings();
            if (array_key_exists('winnerCriteria', $variantSettings)) {
                continue;
            }

            $childSettings = $email->getVariantChildren()->first()->getVariantSettings();
            if (array_key_exists('winnerCriteria', $childSettings)) {
                $variantSettings['winnerCriteria'] = $childSettings['winnerCriteria'];
                $variantSettings['totalWeight']    = 100;

                $email->setVariantSettings($variantSettings);
                $em->persist($email);

                if (0 === ($i % $batchSize)) {
                    $em->flush();
                    $em->clear();
                }
                ++$i;
            }
        }
        $em->flush();
    }
}
