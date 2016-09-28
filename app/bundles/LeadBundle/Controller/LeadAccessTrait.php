<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;


/**
 * Class LeadAccessTrait
 */
trait LeadAccessTrait
{
    /**
     * Determines if the user has access to the lead the note is for
     *
     * @param $leadId
     * @param $action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function checkLeadAccess ($leadId, $action)
    {
        //make sure the user has view access to this lead
        $leadModel = $this->getModel('lead');
        $lead      = $leadModel->getEntity($leadId);

        if ($lead === null) {
            //set the return URL
            $page      = $this->get('session')->get('mautic.lead.page', 1);
            $returnUrl = $this->generateUrl('mautic_contact_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticLeadBundle:Lead:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_contact_index',
                        'mauticContent' => 'leadNote'
                    ],
                    'flashes'         => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.lead.lead.error.notfound',
                            'msgVars' => ['%id%' => $leadId]
                        ]
                    ]
                ]
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'lead:leads:'.$action.'own',
            'lead:leads:'.$action.'other',
            $lead->getPermissionUser()
        )
        ) {

            return $this->accessDenied();
        } else {

            return $lead;
        }
    }
}