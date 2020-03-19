<?php

namespace Mautic\CoreBundle\IpLookup\DoNotSellList;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class MaxMindDoNotSellList implements DoNotSellListInterface
{
    const DEFAULT_BATCH_SIZE = 1000;

    private $batchSize = 0;

    private $position = 0;

    private $list = [];

    private $count = null;

    private $listPath;

    // @todo DELETE THIS WHEN DEV IS DONE!!!!!!!!
    private $mockList = ['44.242.120.158', '2.2.2.2', '3.3.3.3', '4.4.4.4', '5.5.5.5'];

    public function __construct(CoreParametersHelper $coreParametersHelper, int $batchSize = null)
    {
        $this->listPath = $coreParametersHelper->get('maxmind_do_not_sell_list_path') ?? '';
        $this->batchSize = $batchSize ?? self::DEFAULT_BATCH_SIZE;
    }

    /**
     * This method signature supports batching but the file is actually quite small
     * so we may not need to implement this until the file gets much bigger
     */
    public function loadList(int $offset = 0, int $limit = 0): bool
    {
        //Load full list without batching
        if (0 === $offset && 0 === $limit) {
            $this->list = $this->mockList;

            return true;
        }

        $this->list = array_slice($this->mockList, $offset, $limit);

        return boolval(count($this->list));
    }

    public function hasIP(string $ip) {
        return $this->hasIPs([$ip]);
    }

    public function hasIPs(array $ips) {
        // return an array of the ips that are found in the list
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->list[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->list[$this->position]);
    }
}
