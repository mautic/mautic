<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticSocialBundle\Network;

/**
 * Class LinkedInNetwork
 */
class LinkedInNetwork extends AbstractNetwork
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'LinkedIn';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return array(
            'share_button'

        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return false;
    }
}
