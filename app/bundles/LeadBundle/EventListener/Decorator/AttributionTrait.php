<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener\Decorator;

use Mautic\LeadBundle\Model\AttributionModel;

/**
 * Class AttributionTrait
 */
trait AttributionTrait
{
    /**
     * @var AttributionModel
     */
    protected $attributionModel;

    /**
     * @param AttributionModel $attributionModel
     */
    public function setAttributionModel(AttributionModel $attributionModel)
    {
        $this->attributionModel = $attributionModel;
    }
}