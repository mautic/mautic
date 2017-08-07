<?php
namespace Mautic\ScoringBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\ScoringBundle\Entity\ScoringCategory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ScoringCategoryController
 *
 * @author captivea-qch
 */
class ScoringCategoryController extends FormController {
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction() {
        $globalScorings = $this->getDoctrine()->getRepository('MauticScoringBundle:ScoringCategory')->findByIsGlobalScore(true);
        
        // we need to remove any isGlobalScore from a batch delete, cuz they ca'nt be batchDeleted
        $ids = array_map('intval', json_decode($this->request->query->get('ids'), true));
        foreach($globalScorings as $gs) {
            if(in_array($gs->getId(), $ids)) {
                unset($ids[array_search($gs->getId(), $ids)]);
            }
        }
        $this->request->query->set('ids', json_encode(array_map('strval', array_values($ids))));
        return $this->batchDeleteStandard();
    }

    /**
     * @param $objectId
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cloneAction($objectId)
    {
        return $this->cloneStandard($objectId);
    }

    /**
     * @param      $objectId
     * @param bool $ignorePost
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editAction($objectId, $ignorePost = false)
    {
        $sc = $this->getDoctrine()->getRepository('MauticScoringBundle:ScoringCategory')->find($objectId);
        if($sc->getIsGlobalScore()) { // they have to go through URI to come there, so this is acceptable
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        return $this->editStandard($objectId, $ignorePost);
    }

    /**
     * @param int $page
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction($page = 1)
    {
        return $this->indexStandard($page);
    }

    /**
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newAction()
    {
        return $this->newStandard();
    }
    
    /**
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function deleteAction($objectId)
    {
        return $this->deleteStandard($objectId);
    }

    /**
     * {@inheritdoc}
     */
    protected function getControllerBase()
    {
        return 'MauticScoringBundle:ScoringCategory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelName()
    {
        return 'scoring.scoringCategory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteBase()
    {
        return 'scoring';
    }

    /***
     * @param null $objectId
     *
     * @return string
     */
    protected function getSessionBase($objectId = null)
    {
        return 'scoring'.(($objectId) ? '.'.$objectId : '');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTranslationBase()
    {
        return 'mautic.scoring.scoringCategory';
    }
    
    protected function getPermissionBase() {
        return 'point:scoringCategory';
    }
}
