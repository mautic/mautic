<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Annotation\Groups;

trait UuidTrait
{
    #[Groups(['category:read', 'category:write', 'notification:read', 'notification:write', 'company:read', 'company:write', 'leadfield:read', 'leadfield:write', 'page:read', 'page:write', 'campaign:read', 'campaign:write', 'point:read', 'point:write', 'trigger:read', 'trigger:write', 'message:read', 'message:write', 'focus:read', 'focus:write', 'sms:read', 'sms:write', 'asset:read', 'asset:write', 'dynamicContent:read', 'dynamicContent:write', 'form:read', 'form:write', 'stage:read', 'stage:write', 'segment:read', 'segment:write', 'email:read', 'email:write', 'trigger_event:read', 'trigger_event:write', 'event:read', 'event:write', 'field:read', 'field:write', 'action:read', 'action:write', 'download:read', 'download:write', 'channel:read', 'channel:write', 'trigger:read', 'trigger:write', 'monitoring:read', 'monitoring:write', 'report:read', 'report:write', 'role:read', 'role:write', 'permission:read', 'permission:write'])]
    private ?string $uuid = null;

    public static function addUuidField(ClassMetadataBuilder $builder): void
    {
        $builder->createField('uuid', Types::GUID)
            ->nullable()
            ->build();
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): void
    {
        $this->uuid = $uuid;
    }
}
