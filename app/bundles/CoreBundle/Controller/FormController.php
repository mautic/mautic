<?php

namespace Mautic\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
        if (0 !== strpos($sessionBase, 'mautic.')) {
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
     * @return array
     */
    public function getViewArguments(array $args, $action)
    {
        return $this->customizeViewArguments($args, $action);
    }

    /**
     * @deprecated 2.6.0 to be removed in 3.0; use getViewArguments instead
     *
     * @return array
     */
    public function customizeViewArguments($args, $action)
    {
        return $args;
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
        return null !== $this->deprecatedSessionBase ? $this->deprecatedSessionBase : parent::getSessionBase($objectId);
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

    public function __construct(FormFactoryInterface $formFactory, FormFieldHelper $fieldHelper, ManagerRegistry $managerRegistry, MauticFactory $factory, ModelFactory $modelFactory, UserHelper $userHelper, CoreParametersHelper $coreParametersHelper, EventDispatcherInterface $dispatcher, Translator $translator, FlashBag $flashBag, RequestStack $requestStack, CorePermissions $security)
    {
        parent::__construct($formFactory, $fieldHelper, $managerRegistry, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }
}
