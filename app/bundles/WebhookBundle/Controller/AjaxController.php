<?php

namespace Mautic\WebhookBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\WebhookBundle\Exception\PrivateAddressException;
use Mautic\WebhookBundle\Http\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxController extends CommonAjaxController
{
    public function sendHookTestAction(Request $request, Client $client, PathsHelper $pathsHelper): JsonResponse
    {
        try {
            return $this->processWebhookTest($request, $client, $pathsHelper);
        } catch (PrivateAddressException) {
            return $this->createErrorResponse(
                'mautic.webhook.error.private_address'
            );
        } catch (\Exception) {
            return $this->createErrorResponse(
                'mautic.webhook.label.warning'
            );
        }
    }

    private function processWebhookTest(Request $request, Client $client, PathsHelper $pathsHelper): JsonResponse
    {
        $url = $this->validateUrl($request);
        if (!$url) {
            return $this->createErrorResponse('mautic.webhook.label.no.url');
        }

        $selectedTypes        = InputHelper::cleanArray($request->request->all()['types']) ?? [];
        $payloadPaths         = $this->getPayloadPaths($selectedTypes, $pathsHelper);
        $payload              = $this->loadPayloads($payloadPaths);
        $payload['timestamp'] = (new \DateTimeImmutable())->format('c');
        $secret               = InputHelper::string($request->request->get('secret'));

        $response = $client->post($url, $payload, $secret);

        return $this->createResponseFromStatusCode($response->getStatusCode());
    }

    private function validateUrl(Request $request): ?string
    {
        $url = InputHelper::url($request->request->get('url'));

        return '' !== $url ? $url : null;
    }

    private function createResponseFromStatusCode(int $statusCode): JsonResponse
    {
        $isSuccess = str_starts_with((string) $statusCode, '2');
        $message   = $isSuccess
            ? 'mautic.webhook.label.success'
            : 'mautic.webhook.label.warning';

        $cssClass = $isSuccess ? 'has-success' : 'has-error';

        return $this->createJsonResponse($message, $cssClass);
    }

    private function createErrorResponse(string $message): JsonResponse
    {
        return $this->createJsonResponse($message, 'has-error', Response::HTTP_BAD_REQUEST);
    }

    private function createJsonResponse(
        string $message,
        string $cssClass,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $html = sprintf(
            '<div class="%s"><span class="help-block">%s</span></div>',
            $cssClass,
            $this->translator->trans($message)
        );

        return $this->sendJsonResponse(
            ['html' => $html],
            $status
        );
    }

    /*
     * Get an array of all the payload paths we need to load
     *
     * @param $types array
     * @return array
     */
    /**
     * @return non-falsy-string[]
     */
    public function getPayloadPaths($types, PathsHelper $pathsHelper): array
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
            $payloadPath = $pathsHelper->getSystemPath('bundles', true);

            // if plugin is in first part of the string this is an addon
            // input is plugin.bundlename or mautic.bundlename
            if (strpos('plugin.', $prefix)) {
                $payloadPath = $pathsHelper->getSystemPath('plugins', true);
            }

            $prefixParts = explode('.', $prefix);

            $bundleName = array_pop($prefixParts);

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
    /**
     * @return mixed[]
     */
    public function loadPayloads($paths): array
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
