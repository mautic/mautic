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

use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
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
    private $formThemes = [
        'MauticConfigBundle:FormTheme',
    ];

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var BundleHelper
     */
    private $bundleHelper;

    /**
     * @var array
     */
    protected $encodedFields = [];

    /**
     * ConfigBuilderEvent constructor.
     *
     * @param PathsHelper  $pathsHelper
     * @param BundleHelper $bundleHelper
     */
    public function __construct(PathsHelper $pathsHelper, BundleHelper $bundleHelper)
    {
        $this->pathsHelper  = $pathsHelper;
        $this->bundleHelper = $bundleHelper;
    }

    /**
     * Set new form to the forms array.
     *
     * @param $form
     *
     * @return $this
     */
    public function addForm($form)
    {
        if (isset($form['formTheme'])) {
            $this->formThemes[] = $form['formTheme'];
        }

        $this->forms[$form['formAlias']] = $form;

        return $this;
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
     * Helper method can load $parameters array from a config file.
     *
     * @param string $path (relative from the root dir)
     *
     * @return array
     */
    public function getParameters($path = null)
    {
        $paramsFile = $this->pathsHelper->getSystemPath('app').$path;

        if (file_exists($paramsFile)) {
            // Import the bundle configuration, $parameters is defined in this file
            include $paramsFile;
        }

        if (!isset($parameters)) {
            $parameters = [];
        }

        $fields     = $this->getBase64EncodedFields();
        $checkThese = array_intersect(array_keys($parameters), $fields);
        foreach ($checkThese as $checkMe) {
            if (!empty($parameters[$checkMe])) {
                $parameters[$checkMe] = base64_decode($parameters[$checkMe]);
            }
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
            $allBundles = $this->bundleHelper->getMauticBundles(true);
        }

        if (isset($allBundles[$bundle]) && $allBundles[$bundle]['config']['parameters']) {
            return $allBundles[$bundle]['config']['parameters'];
        } else {
            return [];
        }
    }

    /**
     * @param $fields
     *
     * @return $this
     */
    public function addFileFields($fields)
    {
        $this->encodedFields = array_merge($this->encodedFields, (array) $fields);

        return $this;
    }

    /**
     * @return array
     */
    public function getFileFields()
    {
        return $this->encodedFields;
    }
}
