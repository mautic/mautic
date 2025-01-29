<?php

namespace Mautic\UserBundle\Security\SAML\Store;

use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class EntityDescriptorStore implements EntityDescriptorStoreInterface
{
    /**
     * @var EntityDescriptor
     */
    private $entityDescriptor;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function get($entityId): ?EntityDescriptor
    {
        if ($this->entityDescriptor) {
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
        if (!$entityDescriptor) {
            return false;
        }

        return true;
    }

    /**
     * @return array|EntityDescriptor[]
     */
    public function all(): array
    {
        if (!$this->entityDescriptor) {
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
