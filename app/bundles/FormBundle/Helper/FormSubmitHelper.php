<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;

class FormSubmitHelper
{
    /**
     * @param               $tokens
     * @param               $config
     * @param MauticFactory $factory
     * @param Lead          $lead
     */
    public static function sendEmail($tokens, $config, MauticFactory $factory, Lead $lead)
    {
    }

    /**
     * @param      $tokens
     * @param      $config
     * @param Lead $lead
     */
    public static function repostForm($post, $server, $config, Lead $lead)
    {
        if (!empty($config['post_url'])) {
            foreach ($post as $name => $value) {
            }
        }
    }
}
