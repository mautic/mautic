<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModelFactory.
 */
class ModelFactory
{
    /**
     * ModelFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $modelNameKey
     *
     * @return AbstractCommonModel
     */
    public function getModel($modelNameKey)
    {
        // Shortcut for models with the same name as the bundle
        if (strpos($modelNameKey, '.') === false) {
            $modelNameKey = "$modelNameKey.$modelNameKey";
        }

        $parts = explode('.', $modelNameKey);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException($modelNameKey.' is not a valid model key.');
        }

        list($bundle, $name) = $parts;

        $containerKey = str_replace(['%bundle%', '%name%'], [$bundle, $name], 'mautic.%bundle%.model.%name%');

        if ($this->container->has($containerKey)) {
            return $this->container->get($containerKey);
        }

        throw new \InvalidArgumentException($containerKey.' is not a registered container key.');
    }

    /**
     * Check if a model exists.
     *
     * @param $modelNameKey
     */
    public function hasModel($modelNameKey)
    {
        try {
            $this->getModel($modelNameKey);

            return true;
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }
}
