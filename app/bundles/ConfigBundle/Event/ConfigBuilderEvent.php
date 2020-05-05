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
     * @param array $form
     *
     * @return $this
     */
    public function addForm(array $form)
    {
        if (isset($form['formTheme'])) {
            $this->formThemes[] = $form['formTheme'];
        }

        $this->forms[$form['formAlias']] = $form;

        return $this;
    }

    /**
     * Remove a form to the forms array.
     *
     * @param string $formAlias
     *
     * @return bool
     */
    public function removeForm($formAlias)
    {
        if (isset($this->forms[$formAlias])) {
            unset($this->forms[$formAlias]);

            return true;
        }

        return false;
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
     * Get default parameters from config defined in bundles.
     *
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
