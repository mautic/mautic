<?php
/**
 * Created by PhpStorm.
 * User: Sam
 * Date: 05/04/2017
 * Time: 16:58
 */

namespace Mautic\CoreBundle\Helper;


use Mautic\CoreBundle\Model\AbstractCommonModel;

class DataExporterHelper
{

    /**
     * Standard function to generate an array of data via any model's "getEntities" method.
     *
     * Overwrite in your controller if required.
     *
     * @param AbstractCommonModel $model
     * @param array               $args
     * @param callable|null       $resultsCallback
     * @param integer|null        $start
     *
     * @return array
     */
    public function getDataForExport($start, AbstractCommonModel $model, array $args, callable $resultsCallback = null)
    {

        $args['limit'] = $args['limit'] < 200 ? 200 : $args['limit'];
        $args['start'] = $start;

        $results    = $model->getEntities($args);
        $count      = $results['count'];
        $items      = $results['results'];
        $iterations = ceil($count / $args['limit']);
        $loop       = 1;

        // Max of 5 iterations for 1K result export batches
        if ($iterations > 5) {
            $iterations = 5;
        }

        $toExport = [];

        unset($args['withTotalCount']);

        while ($loop <= $iterations) {
            if (is_callable($resultsCallback)) {
                foreach ($items as $item) {
                    $toExport[] = $resultsCallback($item);
                }
            } else {
                foreach ($items as $item) {
                    $toExport[] = (array) $item;
                }
            }

            $args['start'] = ($loop * $args['limit'])+$start;

            $items = $model->getEntities($args);

            $model->getRepository()->clear();

            ++$loop;
        }

        return $toExport;
    }

}