<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

abstract class ApiHelper
{
    /**
     * @param $application
     * @param AbstractIntegration $applicationIntegration
     * @return mixed
     */
    static public function getApiAuth($application, $applicationIntegration)
    {
        switch ($application)
        {
            case 'sugarcrm':
                $integrationAuth = \SugarCRM\Auth\ApiAuth::initiate($applicationIntegration->getSettings());
                break;
        }

        return $integrationAuth;
    }

    /**
     * @param MauticFactory $factory
     * @param string $application
     * @param AbstractIntegration $applicationIntegration
     * @param $integrationAuth
     */
    static public function checkApiAuthentication(MauticFactory $factory, $application, $applicationIntegration)
    {
        $integrationAuth = self::getApiAuth($application, $applicationIntegration);
        if ($integrationAuth->validateAccessToken()) {
            if ($integrationAuth->accessTokenUpdated()) {
                $entity = $applicationIntegration->getEntity();
                $accessTokenData = $integrationAuth->getAccessTokenData();
                $apiSettings = $entity->getApiKeys();
                switch ($application)
                {
                    case 'sugarcrm':
                        $apiSettings['accessToken'] = $accessTokenData['access_token'];
                        $apiSettings['accessTokenExpires'] = $accessTokenData['expires'];
                        break;
                }
                $entity->setApiKeys($apiSettings);
                $em = $factory->getEntityManager();
                $em->persist($entity);
                $em->flush();
            }
        }
    }
}