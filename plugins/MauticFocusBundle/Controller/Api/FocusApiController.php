<?php

namespace MauticPlugin\MauticFocusBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Focus>
 */
class FocusApiController extends CommonApiController
{
    /**
     * @var FocusModel|null
     */
    protected $model;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper, MauticFactory $factory)
    {
        $focusModel = $modelFactory->getModel('focus');
        \assert($focusModel instanceof FocusModel);

        $this->model           = $focusModel;
        $this->entityClass     = Focus::class;
        $this->entityNameOne   = 'focus';
        $this->entityNameMulti = 'focus';
        $this->permissionBase  = 'focus:items';
        $this->dataInputMasks  = [
            'html'   => 'html',
            'editor' => 'html',
        ];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $factory);
    }

    public function generateJsAction($id)
    {
        $focus = $this->model->getEntity($id);
        $view  = $this->view(['js' => $this->model->generateJavascript($focus)], Response::HTTP_OK);

        return $this->handleView($view);
    }
}
