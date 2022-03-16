<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;

/**
 * Class LoadLeadFieldData.
 */
class LoadLeadFieldData extends LeadFieldData
{
    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return [];
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 4;
    }
}
