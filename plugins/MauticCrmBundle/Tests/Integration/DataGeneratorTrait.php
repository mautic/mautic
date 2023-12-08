<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

trait DataGeneratorTrait
{
    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var array
     */
    protected $generatedRecords = [];

    protected function generateData($maxPages): array
    {
        $pageSize = ($this->page === $maxPages) ? ConnectwiseIntegration::PAGESIZE / 2 : ConnectwiseIntegration::PAGESIZE;
        $fakeData = [];
        $counter  = 0;
        while ($counter < $pageSize) {
            $data                     = [
                'id' => $this->id,
            ];
            $fakeData[]               = $data;
            $this->generatedRecords[] = $data;

            ++$counter;
            ++$this->id;
        }
        ++$this->page;

        return $fakeData;
    }

    protected function reset()
    {
        $this->id               = 0;
        $this->page             = 1;
        $this->generatedRecords = [];
    }
}
