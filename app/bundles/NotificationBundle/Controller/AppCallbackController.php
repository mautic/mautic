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
use Mautic\NotificationBundle\Entity\PushID;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppCallbackController extends CommonController
{
    public function indexAction(Request $request)
    {
        $responseBody = [
            'email'   => 'don.gilbert@mautic.com',
            'push_id' => '710a6ea4-aa97-4aff-8a6b-63cc003d7df1',
            'enabled' => true,
        ];

        $em          = $this->get('doctrine.orm.entity_manager');
        $contactRepo = $em->getRepository(Lead::class);

        /** @var Lead $lead */
        $lead = $contactRepo->findOneBy(['email' => $responseBody['email']]);

        if ($lead) {
            $pushIds = $lead->getPushIDs();
            $pushId  = new PushID();
            $matched = false;

            /** @var PushID $id */
            foreach ($pushIds as $id) {
                if ($id->getPushID() === $responseBody['push_id']) {
                    $matched = true;
                    $pushId  = $id;
                    break;
                }
            }

            $pushId->setPushID($responseBody['push_id']);
            $pushId->setEnabled($responseBody['enabled']);

            if ($matched) {
                $lead->removePushID($pushId);
            }

            $lead->addPushID($pushId);
            $em->persist($lead);
        }

        return new Response($request->getContent());
    }
}
