<?php

namespace Mautic\CoreBundle\IpLookup\DoNotSellList;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class MaxMindDoNotSellList implements DoNotSellListInterface
{
    private $position = 0;

    private $list = [];

    private $listPath;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->listPath  = $coreParametersHelper->get('maxmind_do_not_sell_list_path', '');
    }

    public function loadList(): bool
    {
        if (false == $this->listPath) {
            throw new FileNotFoundException('Please configure the path for the Do Not Sell List.');
        }

        if (false !== ($json = file_get_contents($this->listPath))) {
            if ($data = json_decode($json, true)) {
                $this->list = $data;

                return true;
            }
        }

        return false;
    }

    public function getListPath(): string
    {
        return $this->listPath;
    }

    public function setListPath(string $path): void
    {
        $this->listPath = $path;
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
