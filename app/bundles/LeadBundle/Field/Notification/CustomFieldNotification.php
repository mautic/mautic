<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field\Notification;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Exception\NoUserException;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Translation\TranslatorInterface;

class CustomFieldNotification
{
    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        NotificationModel $notificationModel,
        UserModel $userModel,
        TranslatorInterface $translator
    ) {
        $this->notificationModel = $notificationModel;
        $this->userModel         = $userModel;
        $this->translator        = $translator;
    }

    /**
     * @param int $userId
     */
    public function customFieldWasCreated(LeadField $leadField, $userId)
    {
        try {
            $user = $this->getUser($userId);
        } catch (NoUserException $e) {
            return;
        }

        $message = $this->translator->trans(
            'mautic.lead.field.notification.created_message',
            ['%label%' => $leadField->getLabel()]
        );
        $header  = $this->translator->trans('mautic.lead.field.notification.created_header');

        $this->addToNotificationCenter($user, $message, $header);
    }

    /**
     * @param int $userId
     */
    public function customFieldLimitWasHit(LeadField $leadField, $userId)
    {
        try {
            $user = $this->getUser($userId);
        } catch (NoUserException $e) {
            return;
        }

        $message = $this->translator->trans(
            'mautic.lead.field.notification.custom_field_limit_hit_message',
            ['%label%' => $leadField->getLabel()]
        );
        $header  = $this->translator->trans('mautic.lead.field.notification.custom_field_limit_hit_header');

        $this->addToNotificationCenter($user, $message, $header);
    }

    /**
     * @param int $userId
     */
    public function customFieldCannotBeCreated(LeadField $leadField, $userId)
    {
        try {
            $user = $this->getUser($userId);
        } catch (NoUserException $e) {
            return;
        }

        $message = $this->translator->trans(
            'mautic.lead.field.notification.cannot_be_created_message',
            ['%label%' => $leadField->getLabel()]
        );
        $header  = $this->translator->trans('mautic.lead.field.notification.cannot_be_created_header');

        $this->addToNotificationCenter($user, $message, $header);
    }

    /**
     * @param string $message
     * @param string $header
     */
    private function addToNotificationCenter(User $user, $message, $header)
    {
        $this->notificationModel->addNotification(
            $message,
            'info',
            false,
            $header,
            'fa-columns',
            null,
            $user
        );
    }

    /**
     * @param int $userId
     *
     * @return User
     *
     * @throws NoUserException
     */
    private function getUser($userId)
    {
        if (!$userId || !$user = $this->userModel->getEntity($userId)) {
            throw new NoUserException();
        }

        return $user;
    }
}
