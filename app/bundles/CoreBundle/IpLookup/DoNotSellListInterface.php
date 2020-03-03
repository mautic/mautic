<?php

namespace Mautic\CoreBundle\IpLookup;

interface DoNotSellListInterface extends \Iterator
{
    public function loadList(): bool;
}
