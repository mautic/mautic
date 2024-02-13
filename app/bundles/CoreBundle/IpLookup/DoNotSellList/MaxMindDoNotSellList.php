<?php

namespace Mautic\CoreBundle\IpLookup\DoNotSellList;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class MaxMindDoNotSellList implements DoNotSellListInterface
{
    private int $position = 0;

    private $list = [];

    private $listPath;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->listPath  = $coreParametersHelper->get('maxmind_do_not_sell_list_path', '');
    }

    public function loadList(): bool
    {
        $listPath = $this->getListPath();

        if (false == $listPath) {
            throw new BadConfigurationException('Please configure the path to the MaxMind Do Not Sell List.');
        }

        if (!file_exists($listPath)) {
            throw new FileNotFoundException('Please make sure the MaxMind Do Not Sell List file has been downloaded.');
        }

        $json = file_get_contents($listPath);

        if ($data = json_decode($json, true)) {
            $this->list = $data['exclusions'];

            return true;
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

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->list[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->list[$this->position]);
    }
}
