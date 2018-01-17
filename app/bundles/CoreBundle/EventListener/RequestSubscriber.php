<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class RequestSubscriber extends CommonSubscriber
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @param CsrfTokenManagerInterface $tokenManager
     */
    public function __construct(CsrfTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['validateCsrfTokenForAjaxPost', 0],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function validateCsrfTokenForAjaxPost(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($request->isXmlHttpRequest() && $request->getMethod() === Request::METHOD_POST) {
            $csrfRequestToken = $request->headers->get('X-CSRF-Token');
            $csrfSessionToken = $this->tokenManager->getToken('mautic_ajax_post')->getValue();
            if ($csrfSessionToken !== $csrfRequestToken) {
                $message  = $this->translator->trans('mautic.core.error.csrf', [], 'flashes');
                $data     = ['flashes' => ['error' => $message]];
                $content  = $this->templating->render('MauticCoreBundle:Notification:flash_messages.html.php', $data);
                $response = new JsonResponse(['flashes' => $content], Response::HTTP_OK);
                $event->setResponse($response);
                $event->stopPropagation();
            }
        }
    }
}
