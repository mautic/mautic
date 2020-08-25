<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UpdateFieldController extends CommonController
{
    /**
     * @return JsonResponse
     */
    public function updateAction(Request $request, string $integration, string $object, string $field)
    {
        // Clear the session of previously stored fields in case it got stuck
        $session       = $this->get('session');
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
