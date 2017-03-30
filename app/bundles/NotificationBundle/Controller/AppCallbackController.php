<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppCallbackController extends CommonController
{
    public function indexAction(Request $request)
    {
        $requestBody = json_decode($request->getContent(), true);
        $em          = $this->get('doctrine.orm.entity_manager');
        $contactRepo = $em->getRepository(Lead::class);

        /** @var Lead $lead */
        $lead = $contactRepo->findOneBy(['email' => $requestBody['email']]);

        if ($lead) {
            $lead->addPushIDEntry($requestBody['push_id'], $requestBody['enabled']);
            $contactRepo->saveEntity($lead);
        }

        return new Response();
    }
}
