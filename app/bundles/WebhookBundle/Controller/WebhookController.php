<?php

namespace Mautic\WebhookBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\CategoryListFiltersTrait;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebhookController extends FormController
{
    use CategoryListFiltersTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $listFilters = [];

    public function __construct(FormFactoryInterface $formFactory, FormFieldHelper $fieldHelper, ManagerRegistry $doctrine, ModelFactory $modelFactory, UserHelper $userHelper, CoreParametersHelper $coreParametersHelper, EventDispatcherInterface $dispatcher, Translator $translator, FlashBag $flashBag, RequestStack $requestStack, CorePermissions $security)
    {
        $this->setStandardParameters(
            'webhook.webhook', // model name
            'webhook:webhooks', // permission base
            'mautic_webhook', // route base
            'mautic_webhook', // session base
            'mautic.webhook', // lang string base
            '@MauticWebhook/Webhook', // template base
            'mautic_webhook', // activeLink
            'mauticWebhook' // mauticContent
        );

        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request, $page = 1): \Symfony\Component\HttpFoundation\Response
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * @param mixed   $start
     * @param mixed   $limit
     * @param mixed   $filter
     * @param mixed   $orderBy
     * @param mixed   $orderByDir
     * @param mixed[] $args
     *
     * @return array{0: int, 1: array<int, mixed>}
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        $request           = $this->getCurrentRequest();
        $categoryFilters   = $this->applyCategoryListFilter($request, 'mautic.'.$this->getSessionBase().'.list_filters', 'Webhook', 'cat.id', $filter);
        $this->listFilters = $categoryFilters['filters'];

        /** @phpstan-ignore-next-line Deprecated parent class is already used by this controller. */
        return parent::getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @param mixed                $action
     *
     * @return array<string, mixed>
     */
    public function getViewArguments(array $args, $action): array
    {
        if ('index' === $action) {
            $args['viewParameters']['filters'] = $this->listFilters;
        }

        return $args;
    }

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newAction(Request $request)
    {
        return parent::newStandard($request);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Displays details on a Focus.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function viewAction(Request $request, $objectId)
    {
        return $this->viewStandard($request, $objectId, 'webhook', 'webhook', null, 'item');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cloneAction(Request $request, $objectId)
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return parent::batchDeleteStandard($request);
    }
}
