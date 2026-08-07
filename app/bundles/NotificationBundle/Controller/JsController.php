<?php

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;

final class JsController extends CommonController
{
    /**
     * We can't user JsonResponse here, because
     * it improperly encodes the data array.
     */
    public function manifestAction(): Response
    {
        $gcmSenderId = $this->coreParametersHelper->get('gcm_sender_id', '446150739532');
        $data        = [
            'start_url'             => '/',
            'gcm_sender_id'         => $gcmSenderId,
            'gcm_user_visible_only' => true,
        ];

        return new Response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
<<<<<<< HEAD
<<<<<<< HEAD
            Response::HTTP_OK,
=======
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
            Response::HTTP_OK,
>>>>>>> 222589fde5 (cs)
            [
                'Content-Type' => 'application/json',
            ]
        );
    }

    public function workerAction(): Response
    {
        return new Response(
            "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');",
<<<<<<< HEAD
<<<<<<< HEAD
            Response::HTTP_OK,
=======
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
            Response::HTTP_OK,
>>>>>>> 222589fde5 (cs)
            [
                'Service-Worker-Allowed' => '/',
                'Content-Type'           => 'application/javascript',
            ]
        );
    }

    public function updaterAction(): Response
    {
        return new Response(
            "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');",
<<<<<<< HEAD
<<<<<<< HEAD
            Response::HTTP_OK,
=======
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
            Response::HTTP_OK,
>>>>>>> 222589fde5 (cs)
            [
                'Service-Worker-Allowed' => '/',
                'Content-Type'           => 'application/javascript',
            ]
        );
    }
}
