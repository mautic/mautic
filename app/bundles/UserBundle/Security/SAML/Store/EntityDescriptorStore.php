<?php

namespace Mautic\UserBundle\Security\SAML\Store;

use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class EntityDescriptorStore implements EntityDescriptorStoreInterface
{
    private ?EntityDescriptor $entityDescriptor = null;

    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function get($entityId): ?EntityDescriptor
    {
        if ($this->entityDescriptor instanceof \LightSaml\Model\Metadata\EntityDescriptor) {
            return $this->entityDescriptor;
        }

        $this->createEntityDescriptor();

        if ($entityId !== $this->entityDescriptor->getEntityID()) {
            return null;
        }

        return $this->entityDescriptor;
    }

    public function has($entityId): bool
    {
        // SAML is not enabled
        if (!$this->coreParametersHelper->get('saml_idp_metadata')) {
            return false;
        }

        $entityDescriptor = $this->get($entityId);

        // EntityIds do not match
        return null !== $entityDescriptor;
    }

    /**
     * @return array|EntityDescriptor[]
     */
    public function all(): array
    {
        if (!$this->entityDescriptor instanceof \LightSaml\Model\Metadata\EntityDescriptor) {
            $this->createEntityDescriptor();
        }

        return [$this->entityDescriptor];
    }

    private function createEntityDescriptor(): void
    {
        $xml = base64_decode($this->coreParametersHelper->get('saml_idp_metadata'));

        $this->entityDescriptor = EntityDescriptor::loadXml($xml);
    }
}
