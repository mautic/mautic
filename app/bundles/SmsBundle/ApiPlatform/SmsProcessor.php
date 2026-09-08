<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\SmsBundle\Entity\Sms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProcessorInterface<Sms, Sms>
 */
final readonly class SmsProcessor implements ProcessorInterface
{
    private const SCHEDULE_FIELDS = [
        'isPublished'     => true,
        'publishUp'       => true,
        'publishDown'     => true,
        'continueSending' => true,
    ];

    public function __construct(
        private ProcessorInterface $persistProcessor,
        private CorePermissions $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Sms) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $loadedSms = ($context['read_data'] ?? null) instanceof Sms ? $context['read_data'] : null;

        if ($this->requiresPublishPermission($data, $operation, $loadedSms, $context)) {
            // Sms::__clone() intentionally turns domain clones into new, unscheduled
            // entities, so API Platform's previous_data clone cannot be used for
            // ownership checks. read_data is the entity loaded for this request.
            $permissionSubject = $loadedSms ?? $data;

            if (!$this->security->hasPublishAccessForEntity(
                $permissionSubject,
                'sms:smses:publishown',
                'sms:smses:publishother',
            )) {
                throw new AccessDeniedException();
            }
        }

        if ('list' === $data->getSmsType() && !$data->isContinueSending()) {
            $data->setPublishDown(null);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function requiresPublishPermission(
        Sms $sms,
        Operation $operation,
        ?Sms $loadedSms,
        array $context,
    ): bool {
        if ($operation instanceof Post) {
            return $sms->getIsPublished()
                || null !== $sms->getPublishUp()
                || null !== $sms->getPublishDown()
                || $sms->isContinueSending();
        }

        if ($this->hasSubmittedScheduleFields($sms, $context)) {
            return true;
        }

        return $operation instanceof Put
            && null !== $loadedSms
            && $this->hasDifferentSchedule($sms, $loadedSms);
    }

    private function hasDifferentSchedule(Sms $sms, Sms $loadedSms): bool
    {
        return $sms->getIsPublished() !== $loadedSms->getIsPublished()
            || $sms->isContinueSending() !== $loadedSms->isContinueSending()
            || $sms->getPublishUp()?->getTimestamp() !== $loadedSms->getPublishUp()?->getTimestamp()
            || $sms->getPublishDown()?->getTimestamp() !== $loadedSms->getPublishDown()?->getTimestamp();
    }

    /**
     * @param array<string, mixed> $context
     */
    private function hasSubmittedScheduleFields(Sms $sms, array $context): bool
    {
        $request = $context['request'] ?? null;
        if (!$request instanceof Request) {
            return [] !== array_intersect_key($sms->getChanges(), self::SCHEDULE_FIELDS);
        }

        $payload = $request->getPayload()->all();
        if (isset($payload['data'])
            && is_array($payload['data'])
            && isset($payload['data']['attributes'])
            && is_array($payload['data']['attributes'])
        ) {
            $payload = $payload['data']['attributes'];
        }

        return [] !== array_intersect_key($payload, self::SCHEDULE_FIELDS);
    }
}
