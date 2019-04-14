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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UnexpectedValueException;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class CircularDependencyValidator extends ConstraintValidator
{
    /**
     * @var ListModel
     */
    private $model;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param ListModel    $model
     * @param RequestStack $requestStack
     */
    public function __construct(ListModel $model, RequestStack $requestStack)
    {
        $this->model        = $model;
        $this->requestStack = $requestStack;
    }

    /**
     * @param array      $filters
     * @param Constraint $constraint
     */
    public function validate($filters, Constraint $constraint)
    {
        $dependentSegmentIds = $this->flatten(array_map(function ($id) {
            return $this->reduceToSegmentIds($this->model->getEntity($id)->getFilters());
        }, $this->reduceToSegmentIds($filters)));

        try {
            $segmentId = $this->getSegmentIdFromRequest();
            if (in_array($segmentId, $dependentSegmentIds)) {
                $this->context->addViolation($constraint->message);
            }
        } catch (UnexpectedValueException $e) {
            // Segment ID is not in the request. May be new segment.
        }
    }

    /**
     * @return int
     *
     * @throws UnexpectedValueException
     */
    private function getSegmentIdFromRequest()
    {
        $request     = $this->requestStack->getCurrentRequest();
        $routeParams = $request->get('_route_params');

        if (empty($routeParams['objectId'])) {
            throw new UnexpectedValueException('Segment ID is missing in the request');
        }

        return (int) $routeParams['objectId'];
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    private function reduceToSegmentIds(array $filters)
    {
        $segmentFilters = array_filter($filters, function ($v) {
            return $v['type'] == 'leadlist';
        });

        $segentIdsInFilter = array_column($segmentFilters, 'filter');

        return $this->flatten($segentIdsInFilter);
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function flatten(array $array)
    {
        return array_unique(array_reduce($array, 'array_merge', []));
    }
}
