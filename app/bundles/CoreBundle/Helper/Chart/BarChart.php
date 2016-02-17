<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\AbstactChart;
use Mautic\CoreBundle\Helper\Chart\ChartInterface;
use Doctrine\DBAL\Connection;

/**
 * Class BarChart
 */
class BarChart extends AbstractChart implements ChartInterface
{
    /**
     * Date/time unit
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @var string
     */
    protected $unit;

    /**
     * Limit of items
     *
     * @var integer
     */
    protected $limit;

    /**
     * Date and time to end. Now (null) is default.
     *
     * @var integer
     */
    protected $end;

    /**
     * Order
     *
     * @var string (ASC|DESC)
     */
    protected $order;

    /**
     * Match date/time unit to a humanly readable label
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @var array
     */
    protected $labelFormats = array(
        's' => 'H:i:s',
        'i' => 'H:i',
        'H' => 'l ga',
        'd' => 'jS F', 'D' => 'jS F', // ('D' is BC. Can be removed when all charts use this class)
        'W' => 'W',
        'm' => 'F y', 'M' => 'F y', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'Y',
    );

    /**
     * Defines the basic chart values, generates the time axe labels from it
     *
     * @param string  $unit {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param integer $limit the number of loaded items
     * @param string  $end date
     * @param string  $order (DESC|ASC)
     */
    public function __construct($unit = 'm', $limit = 12, $end = null, $order = 'DESC')
    {
        $this->unit  = $unit;
        $this->limit = $limit;
        $this->end = $end;
        $this->order = $order;
        $this->generateTimeLabels($unit, $limit, $end, $order);
    }

    /**
     * Render chart data
     */
    public function render() {
        return array(
            'labels'   => $this->labels,
            'datasets' => $this->datasets
        );
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param  string $label
     * @param  array  $data
     *
     * @return $this
     */
    public function setDataset($label = null, array $data)
    {
        $datasetId = count($this->datasets);

        $dataWithLabels = [];
        foreach ($data as $key => $value) {
            $dataWithLabels[$this->labels[$key]] = $value;
        }

        $baseData = array(
            'label' => $label,
            'data'  => $dataWithLabels,
        );
        
        $this->datasets[] = array_merge($baseData, $this->colors[$datasetId]);

        return $this;
    }

    /**
     * Generate array of labels from the form data
     *
     * @param  string  $unit
     * @param  integer $limit
     * @param  string  $endDate
     * @param  string  $order
     */
    public function generateTimeLabels($unit, $limit, $endDate = null, $order = 'DESC')
    {
        if (!isset($this->labelFormats[$unit])) {
            throw new \UnexpectedValueException('Date/Time unit "' . $unit . '" is not available for a label.');
        }

        $date    = new \DateTime($endDate);
        $oneUnit = $this->getUnitObject($unit);

        for ($i = 0; $i < $limit; $i++) {
            $this->labels[] = $date->format($this->labelFormats[$unit]);
            if ($order == 'DESC') {
                $date->sub($oneUnit);
            } else {
                $date->add($oneUnit);
            }
        }
        
        $this->labels = array_reverse($this->labels);
    }
}
