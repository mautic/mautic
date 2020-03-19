<?php

namespace Mautic\CoreBundle\IpLookup\DoNotSellList;

interface DoNotSellListInterface extends \Iterator
{
    public function loadList(int $offset = 0, int $limit = 0): bool;
}
