<?php
namespace Mautic\ScoringBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Description of ScoringCategoryApiController
 *
 * @author captivea-qch
 */
class ScoringCategoryApiController extends CommonApiController {
    use LeadAccessTrait;
    
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('scoring.scoringCategory');
        $this->entityClass      = 'Mautic\ScoringBundle\Entity\ScoringCategory';
        $this->entityNameOne    = 'scoring';
        $this->entityNameMulti  = 'scoring';
        $this->serializerGroups = ['scoringDetails'];

        parent::initialize($event);
    }
}
