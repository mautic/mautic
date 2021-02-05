<?php

namespace Mautic\CoreBundle\IpLookup\DoNotSellList;

interface DoNotSellListInterface extends \Iterator
{
    public function loadList(): bool;
}
