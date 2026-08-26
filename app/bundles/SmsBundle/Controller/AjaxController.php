<?php

namespace Mautic\SmsBundle\Controller;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\TokenSorter;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Event\TokensBuildEvent;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    private EmailModel $emailModel;

    private SmsModel $smsModel;

    #[Required]
    public function autowireSmsAjaxController(
        EmailModel $emailModel,
        SmsModel $smsModel,
    ): void {
        $this->emailModel = $emailModel;
        $this->smsModel = $smsModel;
    }

    public function getSmsCountStatsAction(Request $request, BroadcastQuery $broadcastQuery, CacheProviderInterface $cacheProvider): JsonResponse
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

                $pending = $broadcastQuery->getPendingCount($sms);
                $cacheProvider->getSimpleCache()->set(sprintf('%s|%s|%s', 'sms', $sms->getId(), 'pending'), $pending);
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

    public function getBuilderTokensAction(Request $request, TokenSorter $tokenSorter, ?EventDispatcherInterface $eventDispatcher = null): JsonResponse
    {
        $query = $request->query->get('query', '');

        $tokens = $this->getBuilderTokens($query);
        $event  = new TokensBuildEvent($tokens);
        $eventDispatcher->dispatch($event, SmsEvents::ON_SMS_TOKENS_BUILD);
        $sortedTokens = $tokenSorter->sortTokens($event->getTokens());

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
