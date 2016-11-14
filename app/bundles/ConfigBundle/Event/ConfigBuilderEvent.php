<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Event;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ConfigEvent.
 */
class ConfigBuilderEvent extends Event
{
    /**
     * @var array
     */
    private $forms = [];

    /**
     * @var array
     */
    private $formThemes = [];

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Set new form to the forms array.
     *
     * @param array $form
     */
    public function addForm($form)
    {
        if (isset($form['formTheme'])) {
            $this->formThemes[] = $form['formTheme'];
        }

        $this->forms[$form['formAlias']] = $form;
    }

    /**
     * Returns the forms array.
     *
     * @return array
     */
    public function getForms()
    {
        return $this->forms;
    }

    /**
     * Returns the formThemes array.
     *
     * @return array
     */
    public function getFormThemes()
    {
        return $this->formThemes;
    }

    /**
     * Returns the factory.
     *
     * @return MauticFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Helper method can load $parameters array from a config file.
     *
     * @param string $path (relative from the root dir)
     *
     * @return array
     */
    public function getParameters($path = null)
    {
        $paramsFile = $this->factory->getSystemPath('app').$path;

        if (file_exists($paramsFile)) {
            // Import the bundle configuration, $parameters is defined in this file
            include $paramsFile;
        }

        if (!isset($parameters)) {
            $parameters = [];
        }

        return $parameters;
    }

    /**
     * @param $bundle
     *
     * @return array
     */
    public function getParametersFromConfig($bundle)
    {
        static $allBundles;

        if (empty($allBundles)) {
            $allBundles = $this->factory->getMauticBundles(true);
        }

        if (isset($allBundles[$bundle]) && $allBundles[$bundle]['config']['parameters']) {
            return $allBundles[$bundle]['config']['parameters'];
        } else {
            return [];
        }
    }
}
