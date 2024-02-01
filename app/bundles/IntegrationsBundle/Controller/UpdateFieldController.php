<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UpdateFieldController extends CommonController
{
    public function updateAction(Request $request, string $integration, string $object, string $field): JsonResponse
    {
        // Clear the session of previously stored fields in case it got stuck
        $session       = $request->getSession();
        $updatedFields = $session->get(sprintf('%s-fields', $integration), []);

        if (!isset($updatedFields[$object])) {
            $updatedFields[$object] = [];
        }

        if (!isset($updatedFields[$object][$field])) {
            $updatedFields[$object][$field] = [];
        }

        if ($mappedField = $request->request->get('mappedField')) {
            $updatedFields[$object][$field]['mappedField'] = $mappedField;
        }

        if ($syncDirection = $request->request->get('syncDirection')) {
            $updatedFields[$object][$field]['syncDirection'] = $syncDirection;
        }

        $session->set(sprintf('%s-fields', $integration), $updatedFields);

        return new JsonResponse([]);
    }
}
