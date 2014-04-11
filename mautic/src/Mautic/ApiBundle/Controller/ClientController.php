<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ClientController extends CommonController
{

   public function apiClientsAction()
   {
       $me      = $this->get('security.context')->getToken()->getUser();
       $clients = $me->getClients()->toArray();

       return $this->render('MauticApiBundle:Client:apiClients.html.php', array('clients' => $clients));
   }

   public function revokeAction($clientId)
   {
       $success = 0;
       $flashes = array();
       if ($this->request->getMethod() == 'POST') {
           $result = $this->container->get('mautic.model.client')->deleteEntity($clientId, false);
           $name   = $result->getName();

           if ($result) {
               $flashes[] = array(
                   'type'    => 'notice',
                   'msg'     => 'mautic.api.client.notice.deleted',
                   'msgVars' => array(
                       '%name%' => $name
                   )
               );
           }
       }
       $returnUrl = $this->generateUrl('mautic_user_account');
       return $this->postActionRedirect(array(
           'returnUrl'       => $returnUrl,
           'contentTemplate' => 'MauticUserBundle:Profile:index',
           'passthroughVars' => array(
               'route'         => $returnUrl,
               'success'       => $success
           ),
           'flashes'         => $flashes
       ));
   }
}