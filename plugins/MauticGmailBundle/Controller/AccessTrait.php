<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGmailBundle\Controller;


/**
 * Class AccessTrait
 */
trait AccessTrait
{
    /**
     * Determines if the user has access to the lead the note is for
     *
     * @param $leadId
     * @param $action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function checkAccess ($leadId, $action)
    {
        /** @var LeadModel $model */
        $model = $this->getModel('lead');
        //make sure the user has view access to this lead
        $lead      = $model->getEntity($leadId);

        if ($lead === null) {
            //set the return URL
            $page      = $this->get('session')->get('mautic.gmail.page', 1);
            $returnUrl = $this->generateUrl('mautic_gmail_timeline_index', ['page' => $page]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticGmailBundle:Timeline:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_gmail_timeline_index',
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
            $lead->getOwner()
        )
        ) {

            return $this->accessDenied();
        } else {

            return $lead;
        }
    }

    /**
     * Returns leads the user has access to
     *
     * @param $action
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function checkAllAccess ($action)
    {
        /** @var LeadModel $model */
        $model = $this->getModel('lead');

        //make sure the user has view access to leads
        $repo = $model->getRepository();
        $leads = $repo->getEntities();

        if ($leads === null) {

            return $this->accessDenied();
        }

        foreach($leads as $lead){
            if (!$this->get('mautic.security')->hasEntityAccess(
                'lead:leads:'.$action.'own',
                'lead:leads:'.$action.'other',
                $lead->getOwner()
            )
            ) {
                unset($lead);
            }
        }

        return $leads;
    }
}