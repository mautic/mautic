<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Symfony\Component\Console\Input\InputInterface;

trait ContactIdsInputTrait
{
    /**
     * @param InputInterface $input
     *
     * @return array
     */
    private function getContactIds(InputInterface $input)
    {
        $string = $input->getOption('contact-ids');
        if ($string) {
            return array_map(
                function ($id) {
                    return (int) trim($id);
                },
                explode(',', $string)
            );
        }

        return [];
    }
}
