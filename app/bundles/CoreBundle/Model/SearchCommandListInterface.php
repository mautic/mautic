<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

interface SearchCommandListInterface
{
    /**
     * @return list<string>
     */
    public function getCommandList();
}
