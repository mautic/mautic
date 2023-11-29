<?php

namespace Mautic\PluginBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Integration>
 */
class IntegrationRepository extends CommonRepository
{
    /**
     * @return mixed[]
     */
    public function getIntegrations(): array
    {
        $services = $this->createQueryBuilder('i')
            ->join('i.plugin', 'p')
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($services as $s) {
            $results[$s->getName()] = $s;
        }

        return $results;
    }

    /**
     * Get core (no plugin) integrations.
     *
     * @return mixed[]
     */
    public function getCoreIntegrations(): array
    {
        $services = $this->createQueryBuilder('i')
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($services as $s) {
            $results[$s->getName()] = $s;
        }

        return $results;
    }
}
