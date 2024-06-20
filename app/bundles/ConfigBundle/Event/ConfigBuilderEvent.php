<?php

namespace Mautic\ConfigBundle\Event;

use Mautic\CoreBundle\Helper\BundleHelper;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigBuilderEvent extends Event
{
    /**
     * @var mixed[]
     */
    private array $forms = [];

    /**
     * @var string[]
     */
    private array $formThemes = [
        '@MauticConfig/FormTheme/_config_file_row.html.twig',
        '@MauticConfig/FormTheme/dsn_row.html.twig',
    ];

    /**
     * @var string[]
     */
    protected array $encodedFields = [];

    public function __construct(
        private BundleHelper $bundleHelper
    ) {
    }

    /**
     * Set new form to the forms array.
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
     */
    public function removeForm($formAlias): bool
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
