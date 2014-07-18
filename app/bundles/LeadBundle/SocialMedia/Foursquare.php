<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\SocialMedia;

class Foursquare extends SocialIntegrationHelper
{

    public function getService()
    {
        return 'Foursquare';
    }

    public function getAuthenticationUrl()
    {
        return 'https://foursquare.com/oauth2/authenticate';
    }

    public function getAccessTokenUrl()
    {
        return 'https://foursquare.com/oauth2/access_token';
    }
}