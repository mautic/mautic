<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class GrapesJsBuilderRepository extends CommonRepository
{
    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'gjb';
    }
}
