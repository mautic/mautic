<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides translated flash messages.
 */
class FlashBag
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param Session             $session
     * @param TranslatorInterface $translator
     * @param RequestStack        $requestStack
     * @param NotificationModel   $notificationModel
     */
    public function __construct(
        Session $session,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        NotificationModel $notificationModel
    ) {
        $this->session           = $session;
        $this->translator        = $translator;
        $this->requestStack      = $requestStack;
        $this->notificationModel = $notificationModel;
    }

    /**
     * @param string     $message
     * @param array|null $messageVars
     * @param string     $type
     * @param string     $domain
     * @param bool       $addNotification
     */
    public function add($message, $messageVars = [], $type = 'notice', $domain = 'flashes', $addNotification = false)
    {
        if ($domain === false) {
            //message is already translated
            $translatedMessage = $message;
        } else {
            if (isset($messageVars['pluralCount'])) {
                $translatedMessage = $this->translator->transChoice($message, $messageVars['pluralCount'], $messageVars, $domain);
            } else {
                $translatedMessage = $this->translator->trans($message, $messageVars, $domain);
            }
        }

        $this->session->getFlashBag()->add($type, $translatedMessage);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            switch ($type) {
                case 'warning':
                    $iconClass = 'text-warning fa-exclamation-triangle';
                    break;
                case 'error':
                    $iconClass = 'text-danger fa-exclamation-circle';
                    break;
                case 'notice':
                    $iconClass = 'fa-info-circle';
                    break;
                default:
                    $iconClass = 'fa-info-circle';
                    break;
            }

            //If the user has not interacted with the browser for the last 30 seconds, consider the message unread
            $lastActive = $this->requestStack->getCurrentRequest()->get('mauticUserLastActive', 0);
            $isRead     = $lastActive > 30 ? 0 : 1;

            $this->notificationModel->addNotification($message, $type, $isRead, null, $iconClass);
        }
    }
}
