<?php

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Model\NotificationModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides translated flash messages.
 */
class FlashBag
{
    const LEVEL_ERROR     = 'error';
    const LEVEL_WARNING   = 'warning';
    const LEVEL_NOTICE    = 'notice';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
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
     * @param string     $level
     * @param string     $domain
     * @param bool       $addNotification
     */
    public function add($message, $messageVars = [], $level = self::LEVEL_NOTICE, $domain = 'flashes', $addNotification = false)
    {
        if (false === $domain) {
            //message is already translated
            $translatedMessage = $message;
        } else {
            if (isset($messageVars['pluralCount']) && empty($messageVars['%count%'])) {
                $messageVars['%count%'] = $messageVars['pluralCount'];
            }

            $translatedMessage = $this->translator->trans($message, $messageVars, $domain);
        }

        $this->session->getFlashBag()->add($level, $translatedMessage);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            switch ($level) {
                case self::LEVEL_WARNING:
                    $iconClass = 'text-warning fa-exclamation-triangle';
                    break;
                case self::LEVEL_ERROR:
                    $iconClass = 'text-danger fa-exclamation-circle';
                    break;
                default:
                    $iconClass = 'fa-info-circle';
                    break;
            }

            //If the user has not interacted with the browser for the last 30 seconds, consider the message unread
            $lastActive = $this->requestStack->getCurrentRequest()->get('mauticUserLastActive', 0);
            $isRead     = $lastActive > 30 ? 0 : 1;

            $this->notificationModel->addNotification($message, $level, $isRead, null, $iconClass);
        }
    }
}
