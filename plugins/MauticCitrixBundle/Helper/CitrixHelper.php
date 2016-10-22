<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class CitrixHelper extends AbstractFormFieldHelper
{
    /** @var Container $container */
    private static $container;

    public static function init(Container $container)
    {
        self::$container = $container;
    }

    public static function getContainer()
    {
        return self::$container;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return [];
    }

    /**
     * Set the translation key prefix.
     */
    public function setTranslationKeyPrefix()
    {
        $this->translationKeyPrefix = 'mautic.plugin.citrix.field.type.';
    }

    /**
     * @param $listType string Can be one of 'webinars', 'meetings', 'trainings' or 'assists'
     * @return array
     */
    public static function getCitrixChoices($listType)
    {
        $integration = 'Gotowebinar';
        /** @var Logger $logger */
        $logger = self::$container->get('monolog.logger.mautic');

        // get secret from plugin settings
        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = self::$container->get('mautic.helper.integration');
        $myIntegration     = $integrationHelper->getIntegrationObject($integration);

        if (!$myIntegration) {
            $logger->log('error', $integration.': integration not found');

            return [];
        }

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()){
            $logger->log('error', $integration.': integration is not enabled');
            return [];
        }
        $keys = $myIntegration->getDecryptedApiKeys();

        $list = [];

        if ('webinars' === $listType) {
            $list = [
                'webinar0' => 'First Webinar',
                'webinar1' => 'Second Webinar',
                'webinar2' => 'Third Webinar',
            ];
        } else if ('meetings' === $listType) {
            $list = [
                '0' => 'First Meeting',
                '1' => 'Second Meeting',
                '2' => 'Third Meeting',
            ];
        } else if ('trainings' === $listType) {
            $list = [
                '0' => 'First Training',
                '1' => 'Second Training',
                '2' => 'Third Training',
            ];
        } else if ('assists' === $listType) {
            $list = [
                '0' => 'First Support Session',
                '1' => 'Second Support Session',
                '2' => 'Third Support Session',
            ];
        }
        return $list;
    }


}
