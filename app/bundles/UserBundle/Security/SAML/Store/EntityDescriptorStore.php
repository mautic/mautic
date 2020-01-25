<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\SAML\Store;

use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Store\EntityDescriptor\EntityDescriptorStoreInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class EntityDescriptorStore implements EntityDescriptorStoreInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var EntityDescriptor
     */
    private $entityDescriptor;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
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
        if (!$this->coreParametersHelper->getParameter('saml_idp_metadata')) {
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
        $xml = base64_decode($this->coreParametersHelper->getParameter('saml_idp_metadata'));

        $this->entityDescriptor = EntityDescriptor::loadXml($xml);
    }
}
