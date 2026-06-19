<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

trait DataGeneratorTrait
{
    protected int $page = 1;

    protected int $id = 0;

    /** @var array<int, array{id: int}> */
    protected array $generatedRecords = [];

    /** @return array<int, array{id: int}> */
    protected function generateData(int $maxPages): array
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

    protected function reset(): void
    {
        $this->id               = 0;
        $this->page             = 1;
        $this->generatedRecords = [];
    }
}
