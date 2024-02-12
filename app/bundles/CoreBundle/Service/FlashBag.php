<?php

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Model\NotificationModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides translated flash messages.
 */
class FlashBag
{
    public const LEVEL_ERROR     = 'error';

    public const LEVEL_WARNING   = 'warning';

    public const LEVEL_NOTICE    = 'notice';

    public function __construct(
        private Session $session,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        private NotificationModel $notificationModel
    ) {
    }

    /**
     * @param string     $message
     * @param array|null $messageVars
     * @param string     $level
     * @param string     $domain
     * @param bool       $addNotification
     */
    public function add($message, $messageVars = [], $level = self::LEVEL_NOTICE, $domain = 'flashes', $addNotification = false): void
    {
        if (false === $domain) {
            // message is already translated
            $translatedMessage = $message;
        } else {
            if (isset($messageVars['pluralCount']) && empty($messageVars['%count%'])) {
                $messageVars['%count%'] = $messageVars['pluralCount'];
            }

            $translatedMessage = $this->translator->trans($message, $messageVars, $domain);
        }

        $this->session->getFlashBag()->add($level, $translatedMessage);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            $iconClass = match ($level) {
                self::LEVEL_WARNING => 'text-warning fa-exclamation-triangle',
                self::LEVEL_ERROR   => 'text-danger fa-exclamation-circle',
                default             => 'fa-info-circle',
            };

            // If the user has not interacted with the browser for the last 30 seconds, consider the message unread
            $lastActive = $this->requestStack->getCurrentRequest()->get('mauticUserLastActive', 0);
            $isRead     = $lastActive > 30 ? 0 : 1;

            $this->notificationModel->addNotification($message, $level, $isRead, null, $iconClass);
        }
    }
}
