<?php

namespace Mautic\SmsBundle\Callback;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

interface CallbackInterface
{
    /**
     * Returns a "transport" string to match the URL path /sms/{transport}/callback.
     *
     * @return string
     */
    public function getTransportName();

    /**
     * Return all contacts that match whatever identifiers the service provides (likely number).
     *
     * @return ArrayCollection
     *
     * @throws NumberNotFoundException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function getContacts(Request $request);

    /**
     * Extract the message in the reply from the request.
     *
     * @return string
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function getMessage(Request $request);
}
