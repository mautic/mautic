<?php

namespace Mautic\SmsBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\TokenSorter;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Event\TokensBuildEvent;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    private EmailModel $emailModel;

    private SmsModel $smsModel;
    private BroadcastQuery $broadcastQuery;
    private CacheStorageHelper $cacheStorageHelper;
    private TokenSorter $smsTokenSorter;

    #[Required]
    public function autowireSmsAjaxController(
        EmailModel $emailModel,
        SmsModel $smsModel,
        BroadcastQuery $broadcastQuery,
        CacheStorageHelper $cacheStorageHelper,
        TokenSorter $smsTokenSorter,
    ): void {
        $this->emailModel = $emailModel;
        $this->smsModel = $smsModel;
        $this->broadcastQuery = $broadcastQuery;
        $this->cacheStorageHelper = $cacheStorageHelper;
        $this->smsTokenSorter = $smsTokenSorter;
    }

    public function getSmsCountStatsAction(Request $request): JsonResponse
    {
        $id  = $request->get('id');
        $ids = $request->query->all()['ids'] ?? [];

        // Support for legacy calls
        if (!$ids && $id) {
            $ids = [$id];
        }

        $data = [];
        foreach ($ids as $id) {
            if ($sms = $this->smsModel->getEntity($id)) {
                if ('list' !== $sms->getSmsType()) {
                    continue;
                }

                $pending = $this->broadcastQuery->getPendingCount($sms);
                $this->cacheStorageHelper->set(sprintf('%s|%s|%s', 'sms', $sms->getId(), 'pending'), $pending);
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

    public function getBuilderTokensAction(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        $tokens = $this->getBuilderTokens($query);
        $event  = new TokensBuildEvent($tokens);
        $this->dispatcher->dispatch($event, SmsEvents::ON_SMS_TOKENS_BUILD);
        $sortedTokens = $this->smsTokenSorter->sortTokens($event->getTokens());

        return $this->sendJsonResponse(['tokens' => $sortedTokens]);
    }

    /**
     * Just selected get tokens from email builder.
     *
     * @return array<string, string>
     */
    private function getBuilderTokens(string $query): array
    {
        $components   = $this->emailModel->getBuilderComponents(null, ['tokens'], $query);
        $findTokens   = ['{contactfield=', '{assetlink', '{pagelink'];
        $returnTokens = [];
        $tokens       = $components['tokens'];

        array_map(
            function (string $token, string $value) use ($findTokens, &$returnTokens): void {
                foreach ($findTokens as $findToken) {
                    if (str_starts_with($token, $findToken)) {
                        $returnTokens[$token] = $value;
                    }
                }
            }, array_keys($tokens), $tokens);

        return $returnTokens;
    }
}
