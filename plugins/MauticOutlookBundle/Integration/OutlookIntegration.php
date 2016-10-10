<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOutlookBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class OutlookIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'Outlook';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        // Just use none for now and I'll build in "basic" later
        return 'none';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
<<<<<<< HEAD:plugins/MauticOutlookBundle/Integration/OutlookIntegration.php
        return array(
            'secret'     => 'mautic.integration.outlook.secret',
        );
=======
        return [
            'secret' => 'mautic.integration.gmail.secret',
        ];
>>>>>>> refs/remotes/mautic/staging:plugins/MauticGmailBundle/Integration/GmailIntegration.php
    }
}
