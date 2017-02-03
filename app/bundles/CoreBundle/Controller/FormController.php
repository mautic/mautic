<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

/**
 * Class FormController.
 *
 * @deprecated 2.3 - to be removed in 3.0; use AbstractFormController instead
 */
class FormController extends AbstractStandardFormController
{
    private $deprecatedModelName;
    private $deprecatedPermissionBase;
    private $deprecatedRouteBase;
    private $deprecatedSessionBase;
    private $deprecatedTranslationBase;
    private $deprecatedTemplateBase;
    private $deprecatedMauticContent;
    protected $activeLink;

    /**
     * @deprecated 2.3 - to be removed in 3.0; extend AbstractStandardFormController instead
     *
     * @param string $modelName       The model for this controller
     * @param string $permissionBase  Permission base for the model (i.e. form.forms or addon.yourAddon.items)
     * @param string $routeBase       Route base for the controller routes (i.e. mautic_form or custom_addon)
     * @param string $sessionBase     Session name base for items saved to session such as filters, page, etc
     * @param string $translationBase Language string base for the shared strings
     * @param string $templateBase    Template base (i.e. YourController:Default) for the view/controller
     * @param string $activeLink      Link ID to return via ajax response
     * @param string $mauticContent   Mautic content string to return via ajax response for onLoad functions
     */
    protected function setStandardParameters(
        $modelName,
        $permissionBase,
        $routeBase,
        $sessionBase,
        $translationBase,
        $templateBase = null,
        $activeLink = null,
        $mauticContent = null
    ) {
        $this->deprecatedModelName      = $modelName;
        $this->deprecatedPermissionBase = $permissionBase;
        if (strpos($sessionBase, 'mautic.') !== 0) {
            $sessionBase = 'mautic.'.$sessionBase;
        }
        $this->deprecatedSessionBase     = $sessionBase;
        $this->deprecatedRouteBase       = $routeBase;
        $this->deprecatedTranslationBase = $translationBase;
        $this->activeLink                = $activeLink;
        $this->deprecatedMauticContent   = $mauticContent;
        $this->deprecatedTemplateBase    = $templateBase;
    }

    /**
     * @param $args
     * @param $action
     *
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @return array
     */
    public function customizeViewArguments($args, $action)
    {
        return $this->getViewArguments($args, $action);
    }

    /**
     * @return mixed
     */
    protected function getModelName()
    {
        return $this->deprecatedModelName;
    }

    /**
     * @return mixed
     */
    protected function getJsLoadMethodPrefix()
    {
        return $this->deprecatedMauticContent;
    }

    /**
     * @return mixed
     */
    protected function getRouteBase()
    {
        return $this->deprecatedRouteBase;
    }

    /**
     * @param null $objectId
     *
     * @return mixed
     */
    protected function getSessionBase($objectId = null)
    {
        return $this->deprecatedSessionBase;
    }

    /**
     * @return mixed
     */
    protected function getTemplateBase()
    {
        return $this->deprecatedTemplateBase;
    }

    /**
     * @return mixed
     */
    protected function getControllerBase()
    {
        return $this->deprecatedTemplateBase;
    }

    /**
     * @return mixed
     */
    protected function getTranslationBase()
    {
        return $this->deprecatedTranslationBase;
    }

    /**
     * @return mixed
     */
    protected function getPermissionBase()
    {
        return $this->deprecatedPermissionBase;
    }
}
