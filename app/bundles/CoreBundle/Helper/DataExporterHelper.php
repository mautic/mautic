<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Model\AbstractCommonModel;

final class DataExporterHelper
{
    /**
     * Standard function to generate an array of data via any model's "getEntities" method.
     *
     * Overwrite in your controller if required.
     *
     * @param int|null                               $start
     * @param AbstractCommonModel<T>                 $model
     * @param (callable(array<mixed>): mixed[])|null $fetcher returns the items for one page of args; defaults to the model's getEntities() results
     *
     * @template T of object
     *
     * @return mixed[]|null
     */
    public function getDataForExport(
        $start,
        AbstractCommonModel $model,
        array $args,
        ?callable $resultsCallback = null,
        bool $skipOrdering = false,
        ?callable $fetcher = null,
    ): ?array {
        $args['limit']        = max($args['limit'], 200);
        $args['start']        = $start;
        $args['skipOrdering'] = $skipOrdering;

        $items = null !== $fetcher ? $fetcher($args) : $model->getEntities($args)['results'];
        if (0 === count($items)) {
            return null;
        }

        $toExport = [];

        unset($args['withTotalCount']);

        if (is_callable($resultsCallback)) {
            foreach ($items as $item) {
                $row = array_map(fn ($itemEncode): string => html_entity_decode((string) $itemEncode, ENT_QUOTES), $resultsCallback($item));

                $toExport[] = $this->secureAgainstCsvInjection($row);
            }
        } else {
            foreach ($items as $item) {
                $toExport[] = $this->secureAgainstCsvInjection((array) $item);
            }
        }

        $model->getRepository()->detachEntities(array_filter($items, is_object(...)));

        return $toExport;
    }

    private function secureAgainstCsvInjection(array $row): array
    {
        foreach ($row as $colNum => $colVal) {
            if ($colVal && in_array(substr($colVal, 0, 1), ['+', '-', '=', '@'])) {
                $row[$colNum] = ' '.$colVal;
            }
        }

        return $row;
    }
}
