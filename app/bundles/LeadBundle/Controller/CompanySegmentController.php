<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanySegmentController extends AbstractStandardFormController
{
    public const SESSION_KEY = 'company_segments';

    /**
     * @return Response|array<mixed>
     */
    public function indexAction(Request $request, int $page = 1): Response|array
    {
        $model = $this->getModel(CompanySegmentModel::class);
        \assert($model instanceof CompanySegmentModel);
        $repository = $model->getRepository();

        // set some permissions
        \assert(null !== $this->security);
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                $this->getPermissionBase().':viewother',
                $this->getPermissionBase().':editother',
                $this->getPermissionBase().':deleteother',
            ],
            'RETURN_ARRAY'
        );

        \assert(is_array($permissions));

        if (!(bool) ($permissions['lead:leads:viewother'] ?? false) && !(bool) ($permissions['lead:leads:viewown'] ?? false)) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        // set limits
        $session = $request->getSession();
        $limit   = $session->get('mautic.'.$this->getSessionBase().'.limit', $this->coreParametersHelper->get('default_pagelimit'));
        /** @phpstan-ignore-next-line */
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

        /** @var \Doctrine\ORM\Tools\Pagination\Paginator<\Mautic\LeadBundle\Entity\CompanySegment> $items */
        /** @phpstan-ignore-next-line */
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
        $this->updateCountCompaniesCache($companySegmentIds);
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
    private function updateCountCompaniesCache(array $companySegmentIds): void
    {
        $model = $this->getModel(CompanySegmentModel::class);
        \assert($model instanceof CompanySegmentModel);
        foreach ($companySegmentIds as $id) {
            if (!$model->hasSegmentCompanyCountInCache($id)) {
                $model->setSegmentCompanyCountInCache([$id]);
            }
        }
    }

    protected function getModelName(): string
    {
        return CompanySegmentModel::class;
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
}
