<?php

namespace Mautic\WebhookBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
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
        /** @var \Mautic\CategoryBundle\Model\CategoryModel $categoryModel */
        $categoryModel        = $this->getModel('category');
        $categories           = $categoryModel->getLookupResults('Webhook', '', 0);
        $categoryFilterPrefix = $this->translator->trans('mautic.core.searchcommand.category');

        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.core.category.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.categories' => [
                        'options' => $categories,
                        'prefix'  => $categoryFilterPrefix,
                    ],
                ],
            ],
        ];

        $request        = $this->getCurrentRequest();
        $session        = $request->getSession();
        $sessionKey     = 'mautic.'.$this->getSessionBase().'.list_filters';
        $currentFilters = $session->get($sessionKey, []);
        $updatedFilters = $request->get('filters', false);

        if ($updatedFilters) {
            $newFilters     = [];
            $updatedFilters = json_decode($updatedFilters, true);

            if ($updatedFilters) {
                foreach ($updatedFilters as $updatedFilter) {
                    [$clmn, $fltr]       = explode(':', $updatedFilter);
                    $newFilters[$clmn][] = $fltr;
                }

                $currentFilters = $newFilters;
            } else {
                $currentFilters = [];
            }
        }
        $session->set($sessionKey, $currentFilters);

        if (!empty($currentFilters)) {
            $categoryIdsByAlias = [];
            foreach ($categories as $category) {
                if (!empty($category['alias'])) {
                    $categoryIdsByAlias[$category['alias']] = (int) $category['id'];
                }
            }

            $catIds = [];
            foreach ($currentFilters as $type => $typeFilters) {
                if ($type === $categoryFilterPrefix) {
                    $type = 'category';
                }

                if ('category' !== $type) {
                    continue;
                }

                $listFilters['filters']['groups']['mautic.core.filter.categories']['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    if (is_numeric($fltr)) {
                        $catIds[] = (int) $fltr;
                        continue;
                    }

                    if (isset($categoryIdsByAlias[$fltr])) {
                        $catIds[] = $categoryIdsByAlias[$fltr];
                    }
                }
            }

            if (!empty($catIds)) {
                $filter['force'][] = ['column' => 'cat.id', 'expr' => 'in', 'value' => array_values(array_unique($catIds))];
            }
        }

        $this->listFilters = $listFilters;

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
