<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanySegmentController extends AbstractStandardFormController
{
    public const SESSION_KEY            = 'company_segments';

    private const PERMISSION_EDIT_OTHER = ':editother';

    public function indexAction(CompanySegmentModel $model, Request $request, int $page = 1): Response
    {
        $repository = $model->getRepository();

        // set some permissions
        \assert(null !== $this->security);
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                $this->getPermissionBase().':viewother',
                $this->getPermissionBase().self::PERMISSION_EDIT_OTHER,
                $this->getPermissionBase().':deleteother',
            ],
            'RETURN_ARRAY'
        );

        \assert(is_array($permissions));

        if (!(bool) ($permissions['lead:leads:viewother'] ?? false) && !(bool) ($permissions['lead:leads:viewown'] ?? false)) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();
        $limit   = $session->get('mautic.'.$this->getSessionBase().'.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start   = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.'.$this->getSessionBase().'.filter', ''));
        $session->set('mautic.'.$this->getSessionBase().'.filter', $search);

        // do some default filtering
        $orderBy    = $session->get('mautic.'.$this->getSessionBase().'.orderby', $repository->getTableAlias().'.dateModified');
        $orderByDir = $session->get('mautic.'.$this->getSessionBase().'.orderbydir', $this->getDefaultOrderDirection());

        $filter = [
            'string' => $search,
        ];

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        if (!$permissions[$this->getPermissionBase().':viewother']) {
            $translator      = $this->translator;
            $mine            = $translator->trans('mautic.core.searchcommand.ismine');
            $filter['force'] = $mine;
        }

        /** @var \Doctrine\ORM\Tools\Pagination\Paginator<CompanySegment> $items */
        [$count, $items] = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

        $count = !is_numeric($count) ? 0 : (int) $count;
        $limit = !is_numeric($limit) ? 0 : (int) $limit;
        if ($limit <= 0) {
            $limit = 1;
        }

        if (0 !== $count && $count < ($start + 1)) {
            // the number of entities are now less than the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $pages    = (int) ceil($count / $limit);
                $lastPage = 0 !== $pages ? $pages : 1;
            }
            $session->set('mautic.'.$this->getSessionBase().'.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => self::class.'::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_segments_index',
                    'mauticContent' => $this->getJsLoadMethodPrefix(),
                ],
            ]);
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.'.$this->getSessionBase().'.page', $page);

        /** @var array<int, int> $companySegmentIds */
        $companySegmentIds = array_keys(iterator_to_array($items->getIterator()));
        $this->updateCountCompaniesCache($model, $companySegmentIds);
        $companyCounts     = $model->getSegmentCompanyCountFromCache($companySegmentIds);

        $parameters = [
            'items'           => $items,
            'companyCounts'   => $companyCounts,
            'page'            => $page,
            'limit'           => $limit,
            'permissions'     => $permissions,
            'security'        => $this->security,
            'tmpl'            => $tmpl,
            'currentUser'     => $this->user,
            'searchValue'     => $search,
            'translationBase' => $this->getTranslationBase(),
            'permissionBase'  => $this->getPermissionBase(),
            'tableAlias'      => $model->getRepository()->getTableAlias(),
        ];

        return $this->delegateView(
            $this->getViewArguments([
                'viewParameters'  => $parameters,
                'contentTemplate' => '@MauticLead/CompanySegment/index.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_segments_index',
                    'route'         => $this->generateUrl('mautic_company_segments_index', ['page' => $page]),
                    'mauticContent' => $this->getJsLoadMethodPrefix(),
                ],
            ],
                'index'
            )
        );
    }

    /**
     * @param array<int, int> $companySegmentIds
     */
    private function updateCountCompaniesCache(CompanySegmentModel $model, array $companySegmentIds): void
    {
        foreach ($companySegmentIds as $id) {
            if (!$model->hasSegmentCompanyCountInCache($id)) {
                $model->setSegmentCompanyCountInCache([$id]);
            }
        }
    }

    public function newAction(Request $request): Response
    {
        return $this->newStandard($request);
    }

    public function editAction(Request $request, int|string $objectId, bool $ignorePost = false): Response
    {
        return $this->editStandard($request, $objectId, $ignorePost);
    }

    public function viewAction(Request $request, int $objectId): Response
    {
        return $this->viewStandard($request, $objectId, null, null, null, 'segment');
    }

    public function cloneAction(CompanySegmentModel $model, Request $request, int $objectId, bool $ignorePost = false): Response
    {
        \assert(null !== $this->security);

        $segment = $model->getEntity($objectId);
        $page    = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);

        if (!$segment instanceof CompanySegment || null === $segment->getId()) {
            return $this->notFoundRedirect($page, $objectId);
        }

        if (!$this->security->hasEntityAccess(
            true, $this->getPermissionBase().self::PERMISSION_EDIT_OTHER, $segment->getCreatedBy()
        )) {
            $this->throwAccessDenied();
        }

        return $this->editStandard($request, clone $segment, $ignorePost);
    }

    protected function getModelName(): string
    {
        return CompanySegmentModel::class;
    }

    protected function getTemplateBase(): string
    {
        return '@MauticLead/CompanySegment';
    }

    protected function getRouteBase(): string
    {
        return CompanySegment::TABLE_NAME;
    }

    protected function getJsLoadMethodPrefix(): string
    {
        return CompanySegmentModel::PROPERTIES_FIELD;
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.company_segments';
    }

    /**
     * @param int|string|mixed|null $objectId
     */
    protected function getSessionBase($objectId = null): string
    {
        return self::SESSION_KEY;
    }

    /**
     * @param object $entity
     * @param string $action
     * @param mixed  $pass
     */
    protected function afterEntitySave($entity, Form $form, $action, $pass = null): void
    {
        if ('new' === $action) {
            $this->addFlashMessage('mautic.core.notice.created', [
                '%name%'      => $entity->getName(),
                '%menu_link%' => $this->getIndexRoute(),
                '%url%'       => $this->generateUrl($this->getActionRoute(), [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                ]),
            ]);
        }
    }

    /**
     * @param array<mixed> $args
     * @param string       $action
     *
     * @return array<mixed>
     */
    public function getViewArguments(array $args, $action): array
    {
        if ('view' === $action) {
            /** @var CompanySegmentModel $model */
            $model                                     = $this->getModel($this->getModelName());
            $objectId                                  = $args['objectId'];
            $args['viewParameters']['segmentCount']    = current($model->getSegmentCompanyRepository()->getCompanyCount([$objectId]));
            $args['viewParameters']['security']        = $this->security;
            $args['viewParameters']['translationBase'] = $this->getTranslationBase();
            $args['viewParameters']['permissionBase']  = $this->getPermissionBase();
            $args['viewParameters']['permissions']     = $this->security->isGranted(
                [
                    'lead:leads:editown',
                    $this->getPermissionBase().':viewother',
                    $this->getPermissionBase().self::PERMISSION_EDIT_OTHER,
                    $this->getPermissionBase().':deleteother',
                ],
                'RETURN_ARRAY'
            );
        }

        return $args;
    }

    public function deleteAction(Request $request, int $objectId): Response
    {
        return $this->deleteStandard($request, $objectId);
    }

    public function batchDeleteAction(Request $request): Response
    {
        return $this->batchDeleteStandard($request);
    }

    private function notFoundRedirect(int $page, int $objectId): Response
    {
        $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $page]);

        return $this->postActionRedirect([
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => self::class.'::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
            'flashes' => [
                [
                    'type'    => 'error',
                    'msg'     => 'mautic.company_segments.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ],
            ],
        ]);
    }
}
