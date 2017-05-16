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
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    protected $paramHelper;

    public function __construct(CoreParametersHelper $paramHelper)
    {
        $this->paramHelper = $paramHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_PRE_SAVE => ['escapePercentCharacters', 1000],
        ];
    }

    /**
     * @param ConfigEvent $event
     */
    public function escapePercentCharacters(ConfigEvent $event)
    {
        $config = $event->getConfig();

        $escapeInvalidReference = function ($reference) {
            // only escape when the referenced variable doesn't exist
            if ($this->paramHelper->getParameter($reference[1]) === null) {
                return '%'.$reference[0].'%';
            }

            return $reference[0];
        };

        array_walk_recursive($config, function (&$value) use ($escapeInvalidReference) {
            if (is_string($value)) {
                $value = preg_replace_callback('/%(.*?)%/', $escapeInvalidReference, $value);
            }
        });
        $event->setConfig($config);
    }
}
