<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $paramHelper;

    /**
     * @var ConfigChangeLogger
     */
    private $configChangeLogger;

    /**
     * @var Container
     */
    private $container;

    public function __construct(CoreParametersHelper $paramHelper, Container $container, ConfigChangeLogger $configChangeLogger)
    {
        $this->paramHelper        = $paramHelper;
        $this->configChangeLogger = $configChangeLogger;
        $this->container          = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_PRE_SAVE  => ['escapePercentCharacters', 1000],
            ConfigEvents::CONFIG_POST_SAVE => ['onConfigPostSave', 0],
        ];
    }

    public function escapePercentCharacters(ConfigEvent $event)
    {
        $config = $event->getConfig();

        $escapeInvalidReference = function ($reference) {
            // only escape when the referenced variable doesn't exist as either a Mautic config parameter or Symfony container parameter

            try {
                $this->container->getParameter($reference[1]);

                return $reference[0];
            } catch (ParameterNotFoundException $exception) {
            }

            if (!$this->paramHelper->hasParameter($reference[1])) {
                return '%'.$reference[0].'%';
            }

            return $reference[0];
        };

        array_walk_recursive($config, function (&$value) use ($escapeInvalidReference) {
            if (is_string($value)) {
                $value = preg_replace_callback('/%(.*?)%/s', $escapeInvalidReference, $value);
            }
        });
        $event->setConfig($config);
    }

    public function onConfigPostSave(ConfigEvent $event)
    {
        if ($originalNormData = $event->getOriginalNormData()) {
            // We have something to log
            $this->configChangeLogger
                ->setOriginalNormData($originalNormData)
                ->log($event->getNormData());
        }
    }
}
