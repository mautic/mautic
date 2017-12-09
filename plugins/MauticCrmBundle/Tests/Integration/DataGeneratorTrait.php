<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

trait DataGeneratorTrait
{
    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var
     */
    protected $id = 0;

    /**
     * @var array
     */
    protected $generatedRecords = [];

    /**
     * @param $maxPages
     *
     * @return array
     */
    protected function generateData($maxPages)
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
}
