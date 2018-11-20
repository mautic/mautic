<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Controller;

use Mautic\SmsBundle\Callback\HandlerContainer;
use Mautic\SmsBundle\Exception\CallbackHandlerNotFound;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReplyController extends Controller
{
    /**
     * @var HandlerContainer
     */
    private $callbackHandler;

    /**
     * ReplyController constructor.
     *
     * @param HandlerContainer $callbackHandler
     */
    public function __construct(HandlerContainer $callbackHandler)
    {
        $this->callbackHandler = $callbackHandler;
    }

    /**
     * @param Request $request
     * @param string  $transport
     *
     * @return Response
     */
    public function callbackAction(Request $request, $transport)
    {
        try {
            $handler = $this->callbackHandler->getHandler($transport);
        } catch (CallbackHandlerNotFound $exception) {
            throw new NotFoundHttpException();
        }

        $handler->processCallbackRequest($request);

        return new Response();
    }
}
