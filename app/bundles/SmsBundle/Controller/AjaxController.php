<?php

namespace Mautic\SmsBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Model\SmsModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    public function getSmsCountStatsAction(Request $request, BroadcastQuery $broadcastQuery, CacheStorageHelper $cacheStorageHelper): JsonResponse
    {
        /** @var SmsModel $model */
        $model = $this->getModel('sms');

        $id  = $request->get('id');
        $ids = $request->get('ids');

        // Support for legacy calls
        if (!$ids && $id) {
            $ids = [$id];
        }

        $data = [];
        foreach ($ids as $id) {
            if ($sms = $model->getEntity($id)) {
                if ('list' !== $sms->getSmsType()) {
                    continue;
                }

                $pending = $broadcastQuery->getPendingCount($sms);
                $cacheStorageHelper->set(sprintf('%s|%s|%s', 'sms', $sms->getId(), 'pending'), $pending);
                if (!$pending) {
                    continue;
                }
                $data[] = [
                    'id'          => $id,
                    'pending'     => $this->translator->trans(
                        'mautic.sms.stat.leadcount',
                        ['%count%' => $pending]
                    ),
                ];
            }
        }

        // Support for legacy calls
        if ($request->get('id')) {
            $data = $data[0];
        } else {
            $data = [
                'success' => 1,
                'stats'   => $data,
            ];
        }

        return new JsonResponse($data);
    }
}
