<?php

namespace Mautic\CoreBundle\Controller;

/**
 * @deprecated 2.3 - to be removed in 3.0; use AbstractFormController instead
 */
class FormController extends AbstractStandardFormController
{
    private string $deprecatedModelName = '';

    private ?string $deprecatedPermissionBase = null;

    private ?string $deprecatedRouteBase = null;

    private ?string $deprecatedSessionBase = null;

    private ?string $deprecatedTranslationBase = null;

    private ?string $deprecatedTemplateBase = null;

    private ?string $deprecatedMauticContent = null;

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
        string $modelName,
        string $permissionBase,
        string $routeBase,
        string $sessionBase,
        string $translationBase,
        string $templateBase,
        string $activeLink,
        string $mauticContent,
    ) {
        $this->deprecatedModelName      = $modelName;
        $this->deprecatedPermissionBase = $permissionBase;
        if (!str_starts_with($sessionBase, 'mautic.')) {
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
     * @return mixed[]
     */
    public function getViewArguments(array $args, $action): array
    {
        return $args;
    }

    protected function getModelName(): string
    {
        return $this->deprecatedModelName;
    }

    protected function getJsLoadMethodPrefix(): ?string
    {
        return $this->deprecatedMauticContent;
    }

    protected function getRouteBase(): ?string
    {
        return $this->deprecatedRouteBase;
    }

    /**
     * @return mixed
     */
    protected function getSessionBase($objectId = null)
    {
        return $this->deprecatedSessionBase ?? parent::getSessionBase($objectId);
    }

    protected function getTemplateBase(): ?string
    {
        return $this->deprecatedTemplateBase;
    }

    protected function getTranslationBase(): ?string
    {
        return $this->deprecatedTranslationBase;
    }

    protected function getPermissionBase(): ?string
    {
        return $this->deprecatedPermissionBase;
    }
}
