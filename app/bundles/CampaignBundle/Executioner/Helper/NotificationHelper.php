<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Helper;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationHelper
{
    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Router
     */
    private $router;

    /**
     * NotificationHelper constructor.
     *
     * @param UserModel           $userModel
     * @param NotificationModel   $notificationModel
     * @param TranslatorInterface $translator
     * @param Router              $router
     */
    public function __construct(UserModel $userModel, NotificationModel $notificationModel, TranslatorInterface $translator, Router $router)
    {
        $this->userModel         = $userModel;
        $this->notificationModel = $notificationModel;
        $this->translator        = $translator;
        $this->router            = $router;
    }

    /**
     * @param Lead  $contact
     * @param Event $event
     */
    public function notifyOfFailure(Lead $contact, Event $event)
    {
        $user = $this->getUser($contact, $event);
        if (!$user || !$user->getId()) {
            return;
        }

        $this->notificationModel->addNotification(
            $event->getCampaign()->getName().' / '.$event->getName(),
            'error',
            false,
            $this->translator->trans(
                'mautic.campaign.event.failed',
                [
                    '%contact%' => '<a href="'.$this->router->generate(
                            'mautic_contact_action',
                            ['objectAction' => 'view', 'objectId' => $contact->getId()]
                        ).'" data-toggle="ajax">'.$contact->getPrimaryIdentifier().'</a>',
                ]
            ),
            null,
            null,
            $user
        );
    }

    /**
     * @param Lead  $contact
     * @param Event $event
     *
     * @return User
     */
    private function getUser(Lead $contact, Event $event)
    {
        // Default is to notify the contact owner
        if ($owner = $contact->getOwner()) {
            return $owner;
        }

        // If the contact doesn't have an owner, notify the one that created the campaign
        if ($campaignCreator = $event->getCampaign()->getCreatedBy()) {
            if ($owner = $this->userModel->getEntity($campaignCreator)) {
                return $owner;
            }
        }

        // If all else fails, notifiy a system admins
        return $this->userModel->getSystemAdministrator();
    }
}
