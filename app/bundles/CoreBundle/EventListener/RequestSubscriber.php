<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Helper\TemplatingHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RequestSubscriber implements EventSubscriberInterface
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TemplatingHelper
     */
    private $templating;

    public function __construct(
        CsrfTokenManagerInterface $tokenManager,
        TranslatorInterface $translator,
        TemplatingHelper $templating
    ) {
        $this->tokenManager = $tokenManager;
        $this->translator   = $translator;
        $this->templating   = $templating;
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

    public function validateCsrfTokenForAjaxPost(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->isAjaxPost($request) && $this->isSecurePath($request) && !$this->isCsrfTokenFromRequestHeaderValid($request)) {
            $message  = $this->translator->trans('mautic.core.error.csrf', [], 'flashes');
            $data     = ['flashes' => ['error' => $message]];
            $content  = $this->templating->getTemplating()->render('MauticCoreBundle:Notification:flash_messages.html.php', $data);
            $response = new JsonResponse(['flashes' => $content], Response::HTTP_OK);
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }

    /**
     * @return bool
     */
    private function isAjaxPost(Request $request)
    {
        return $request->isXmlHttpRequest() && Request::METHOD_POST === $request->getMethod();
    }

    /**
     * @return bool
     */
    private function isSecurePath(Request $request)
    {
        return 1 === preg_match('/^\/s\//', $request->getPathinfo());
    }

    /**
     * @return bool
     */
    private function isCsrfTokenFromRequestHeaderValid(Request $request)
    {
        $csrfRequestToken = $request->headers->get('X-CSRF-Token');
        $csrfSessionToken = $this->tokenManager->getToken('mautic_ajax_post')->getValue();

        return $csrfSessionToken === $csrfRequestToken;
    }
}
