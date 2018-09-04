<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class CircularDependencyValidator extends ConstraintValidator
{
    /**
     * @var ListModel
     */
    protected $model;
    protected $currentSegmentId;

    public function __construct(ListModel $model, $request)
    {
        $this->model            = $model;
        $this->currentSegmentId = (int) $request->getCurrentRequest()->get('_route_params')['objectId'];
    }

    /**
     * @param LeadField  $field
     * @param Constraint $constraint
     */
    public function validate($filters, Constraint $constraint)
    {
        $dependentSegmentIds = $this->flatten(array_map(function ($id) {
            return $this->reduceToSegmentIds($this->model->getEntity($id)->getFilters());
        }, $this->reduceToSegmentIds($filters)));

        if (in_array($this->currentSegmentId, $dependentSegmentIds)) {
            $this->context->addViolation($constraint->message);
        }
    }

    private function reduceToSegmentIds($filters)
    {
        $segmentFilters = array_filter($filters, function ($v) {
            return $v['type'] == 'leadlist';
        });

        $segentIdsInFilter = array_column($segmentFilters, 'filter');

        return $this->flatten($segentIdsInFilter);
    }

    private function flatten($array)
    {
        return array_unique(array_reduce($array, 'array_merge', []));
    }
}
