<?php

namespace Mautic\SmsBundle\Controller;

use Mautic\SmsBundle\Callback\HandlerContainer;
use Mautic\SmsBundle\Exception\CallbackHandlerNotFound;
use Mautic\SmsBundle\Helper\CallbackHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CallbackController extends AbstractController
{
    /**
     * @var HandlerContainer
     */
    private $callbackHandler;

    /**
     * @var CallbackHelper
     */
    private $callbackHelper;

    /**
     * CallbackController constructor.
     */
    public function __construct(HandlerContainer $callbackHandler, CallbackHelper $callbackHelper)
    {
        $this->callbackHandler = $callbackHandler;
        $this->callbackHelper  = $callbackHelper;
    }

    /**
     * @param $transport
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function callbackAction(Request $request, $transport)
    {
        define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        try {
            $handler = $this->callbackHandler->getHandler($transport);
        } catch (CallbackHandlerNotFound $exception) {
            throw new NotFoundHttpException();
        }

        return $this->callbackHelper->handleRequest($handler, $request);
    }
}
