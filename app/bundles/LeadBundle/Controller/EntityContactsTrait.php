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
     * @param       $objectId
     * @param       $page
     * @param       $permission
     * @param       $sessionVar
     * @param       $joinTable         Table to join to obtain list of related contacts
     * @param null  $channel           Channel for this entity
     * @param null  $contactColumnName If the entity ID in $joinTable is not "id", set the column name here
     * @param array $contactFilter     Array of additional filters for the getEntityContactsWithFields() function
     * @param null  $route             Route for this view's contact list
     *
     * @return mixed
     */
    protected function generateContactsGrid($objectId, $page, $permission, $sessionVar, $joinTable, $channel = null, $contactColumnName = null, array $contactFilter = null, $route = null)
    {
        if ($permission && !$this->get('mautic.security')->isGranted($permission)) {
            return $this->accessDenied();
        }

        // Set the route if not standardized
        if (null == $route) {
            $route = "mautic_{$sessionVar}_contacts";
        }

        // Apply filters
        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $search = $this->request->get('search', $this->get('session')->get('mautic.'.$sessionVar.'.contact.filter', ''));
        $this->get('session')->set('mautic.'.$sessionVar.'.contact.filter', $search);

        $filter     = ['string' => $search, 'force' => []];
        $orderBy    = $this->get('session')->get('mautic.'.$sessionVar.'.contact.orderby', 'l.id');
        $orderByDir = $this->get('session')->get('mautic.'.$sessionVar.'.contact.orderbydir', 'DESC');

        //set limits
        $limit = $this->get('session')->get('mautic.'.$sessionVar.'.contact.limit', $this->get('mautic.helper.core_parameters')->getParameter('default_pagelimit'));
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
            $joinTable,
            $objectId,
            $contactFilter,
            $contactColumnName
        );

        $count = $contacts['count'];
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->get('session')->set('mautic.'.$sessionVar.'.contact.page', $lastPage);
            $returnUrl = $this->generateUrl($route, ['objectId' => $objectId, 'page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage, 'objectId' => $objectId],
                    'contentTemplate' => 'MauticLeadBundle:Lead:grid.html.php',
                    'passthroughVars' => [
                        'mauticContent' => $sessionVar.'Contacts',
                    ],
                ]
            );
        }

        // Get DNC for the contact
        $dnc = [];
        if ($channel) {
            $dnc = $this->getDoctrine()->getManager()->getRepository('MauticLeadBundle:DoNotContact')->getChannelList($channel, array_keys($contacts['results']));
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'page'          => $page,
                    'items'         => $contacts['results'],
                    'totalItems'    => $contacts['count'],
                    'tmpl'          => $sessionVar.'Contacts',
                    'indexMode'     => 'grid',
                    'link'          => $route,
                    'sessionVar'    => $sessionVar.'.contact',
                    'limit'         => $limit,
                    'objectId'      => $objectId,
                    'noContactList' => $dnc,
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
