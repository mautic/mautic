<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\LeadBundle\Entity\LeadRepository;

/**
 * Class EntityContactsTrait.
 */
trait EntityContactsTrait
{
    /**
     * @param        $entityId
     * @param        $page
     * @param        $permission
     * @param        $sessionVar
     * @param        $entityJoinTable    Table to join to obtain list of related contacts or a DBAL QueryBuilder object defining custom joins
     * @param null   $dncChannel         Channel for this entity to get do not contact records for
     * @param null   $entityIdColumnName If the entity ID in $joinTable is not "id", set the column name here
     * @param array  $contactFilter      Array of additional filters for the getEntityContactsWithFields() function
     * @param array  $additionalJoins    [ ['type' => 'join|leftJoin', 'from_alias' => '', 'table' => '', 'condition' => ''], ... ]
     * @param string $contactColumnName  Column of the contact in the join table
     * @param string $paginationTarget   DOM seletor for injecting new content when pagination is used
     *
     * @return mixed
     */
    protected function generateContactsGrid(
        $entityId,
        $page,
        $permission,
        $sessionVar,
        $entityJoinTable,
        $dncChannel = null,
        $entityIdColumnName = 'id',
        array $contactFilter = null,
        array $additionalJoins = null,
        $contactColumnName = null,
        array $routeParameters = [],
        $paginationTarget = null
    ) {
        if ($permission && !$this->get('mautic.security')->isGranted($permission)) {
            return $this->accessDenied();
        }

        // Set the route if not standardized
        $route = "mautic_{$sessionVar}_contacts";
        if (method_exists($this, 'getRouteBase') && $this->getRouteBase()) {
            $route = 'mautic_'.$this->getRouteBase().'_contacts';
        }

        // Apply filters
        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters($sessionVar.'.contact');
        }

        $search = $this->request->get('search', $this->get('session')->get('mautic.'.$sessionVar.'.contact.filter', ''));
        $this->get('session')->set('mautic.'.$sessionVar.'.contact.filter', $search);

        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $this->get('session')->get('mautic.'.$sessionVar.'.contact.orderby', 'l.id');
        $orderByDir = $this->get('session')->get('mautic.'.$sessionVar.'.contact.orderbydir', 'DESC');

        //set limits
        $limit = $this->get('session')->get(
            'mautic.'.$sessionVar.'.contact.limit',
            $this->get('mautic.helper.core_parameters')->getParameter('default_pagelimit')
        );

        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        /** @var LeadRepository $repo */
        $repo     = $this->getModel('lead')->getRepository();
        $contacts = $repo->getEntityContacts(
            [
                'withTotalCount' => true,
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
            ],
            $entityJoinTable,
            $entityId,
            $contactFilter,
            $entityIdColumnName,
            $additionalJoins,
            $contactColumnName
        );

        $count = $contacts['count'];
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->get('session')->set('mautic.'.$sessionVar.'.contact.page', $lastPage);
            $returnUrl = $this->generateUrl($route, array_merge(['objectId' => $entityId, 'page' => $lastPage], $routeParameters));

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage, 'objectId' => $entityId],
                    'contentTemplate' => 'MauticLeadBundle:Lead:grid.html.php',
                    'passthroughVars' => [
                        'mauticContent' => $sessionVar.'Contacts',
                    ],
                ]
            );
        }

        // Get DNC for the contact
        $dnc = [];
        if ($dncChannel) {
            $dnc = $this->getDoctrine()->getManager()->getRepository('MauticLeadBundle:DoNotContact')->getChannelList(
                $dncChannel,
                array_keys($contacts['results'])
            );
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'page'            => $page,
                    'items'           => $contacts['results'],
                    'totalItems'      => $contacts['count'],
                    'tmpl'            => $sessionVar.'Contacts',
                    'indexMode'       => 'grid',
                    'route'           => $route,
                    'routeParameters' => $routeParameters,
                    'sessionVar'      => $sessionVar.'.contact',
                    'limit'           => $limit,
                    'objectId'        => $entityId,
                    'noContactList'   => $dnc,
                    'target'          => $paginationTarget,
                ],
                'contentTemplate' => 'MauticLeadBundle:Lead:grid.html.php',
                'passthroughVars' => [
                    'mauticContent' => $sessionVar.'Contacts',
                    'route'         => false,
                ],
            ]
        );
    }
}
