<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

final readonly class ConnectionTestFeature implements AiFeatureInterface
{
    public function __construct(
        private ModelWorkload $workload
    )
    {
    }

    public static function idFor(ModelWorkload $workload): string
    {
        return 'connection_test.'.$workload->value;
    }

    public function id(): string
    {
        return self::idFor($this->workload);
    }

    public function permission(): ?string
    {
        return null;
    }

    public function workload(): ModelWorkload
    {
        return $this->workload;
    }

    public function allowedContextFields(): array
    {
        return [];
    }

    public function instructions(): string
    {
        return 'This is a connection test. Reply with {"ok":true}.';
    }

    public function responseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['ok' => ['const' => true]],
            'required' => ['ok'],
            'additionalProperties' => false,
        ];
    }
}
