<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;

/**
 * Class TimelineController
 */
class TimelineController extends FormController
{

    /**
     * Loads the timeline for a lead
     *
     * @param integer $leadId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($leadId)
    {
        $model   = $this->factory->getModel('lead.lead');
        $lead    = $model->getEntity($leadId);
        $page    = $this->factory->getSession()->get('mautic.lead.page', 1);

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'lead:timeline:viewown',
            'lead:timeline:viewother'
        ), "RETURN_ARRAY");

        if ($lead === null) {
            //set the return URL
            $returnUrl  = $this->generateUrl('mautic_lead_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'lead'
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.lead.lead.error.notfound',
                        'msgVars' => array('%id%' => $leadId)
                    )
                )
            ));
        }

        if (!$this->factory->getSecurity()->hasEntityAccess(
            'lead:timeline:viewown', 'lead:timeline:viewother', $lead->getOwner()
        )) {
            return $this->accessDenied();
        }

        $template      = 'MauticLeadBundle:Timeline:timeline.html.php';
        $vars['route'] = $this->generateUrl('mautic_leadtimeline_view', array('leadId' => $lead->getId()));

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'lead' => $lead,
                'tmpl' => $tmpl
            ),
            'contentTemplate' => 'MauticLeadBundle:Timeline:timeline.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'leadtemplate',
                'route'         => $this->generateUrl('mautic_leadtimeline_view', array('leadId' => $lead->getId()))
            )
        ));
    }
}
