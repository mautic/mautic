<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\WebhookBundle\Http\Client;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    protected function sendHookTestAction(Request $request)
    {
        $url = InputHelper::url($request->request->get('url'));

        // validate the URL
        if ('' == $url || !$url) {
            // default to an error message
            $dataArray = [
                'success' => 1,
                'html'    => '<div class="has-error"><span class="help-block">'
                    .$this->translator->trans('mautic.webhook.label.no.url')
                    .'</span></div>',
            ];

            return $this->sendJsonResponse($dataArray);
        }

        // get the selected types
        $selectedTypes = InputHelper::cleanArray($request->request->get('types'));
        $payloadPaths  = $this->getPayloadPaths($selectedTypes);
        $payloads      = $this->loadPayloads($payloadPaths);
        $now           = new \DateTime();

        $payloads['timestamp'] = $now->format('c');

        // set the response
        /** @var Psr\Http\Message\ResponseInterface $response */
        $response = $this->get('mautic.webhook.http.client')->post($url, $payloads, InputHelper::string($request->request->get('secret')));

        // default to an error message
        $dataArray = [
            'success' => 1,
            'html'    => '<div class="has-error"><span class="help-block">'
                .$this->translator->trans('mautic.webhook.label.warning')
                .'</span></div>',
        ];

        // if we get a 2xx response convert to success message
        if (2 == substr($response->getStatusCode(), 0, 1)) {
            $dataArray['html'] =
                '<div class="has-success"><span class="help-block">'
                .$this->translator->trans('mautic.webhook.label.success')
                .'</span></div>';
        }

        return $this->sendJsonResponse($dataArray);
    }

    /*
     * Get an array of all the payload paths we need to load
     *
     * @param $types array
     * @return array
     */
    public function getPayloadPaths($types)
    {
        $payloadPaths = [];

        foreach ($types as $type) {
            // takes an input like mautic.lead_on_something
            // converts to array pieces using _
            $typePath = explode('_', $type);

            // pull the prefix into its own variable
            $prefix = $typePath[0];

            // now that we have the remove it from the array
            unset($typePath[0]);

            // build the event name by putting the pieces back together
            $eventName = implode('_', $typePath);

            // default the path to core
            $payloadPath = $this->factory->getSystemPath('bundles', true);

            // if plugin is in first part of the string this is an addon
            // input is plugin.bundlename or mautic.bundlename
            if (strpos('plugin.', $prefix)) {
                $payloadPath = $this->factory->getSystemPath('plugins', true);
            }

            $prefixParts = explode('.', $prefix);

            $bundleName = (array_pop($prefixParts));

            $payloadPath .= '/'.ucfirst($bundleName).'Bundle/Assets/WebhookPayload/'.$bundleName.'_'.$eventName.'.json';

            $payloadPaths[$type] = $payloadPath;
        }

        return $payloadPaths;
    }

    /*
     * Iterate through the paths and get the json payloads
     *
     * @param  $paths array
     * @return $payload array
     */
    public function loadPayloads($paths)
    {
        $payloads = [];

        foreach ($paths as $key => $path) {
            if (file_exists($path)) {
                $payloads[$key] = json_decode(file_get_contents($path), true);
            }
        }

        return $payloads;
    }
}
