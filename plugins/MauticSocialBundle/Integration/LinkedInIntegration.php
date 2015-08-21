<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

/**
 * Class LinkedInIntegration
 */
class LinkedInIntegration extends SocialIntegration
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

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return array();
    }
}
