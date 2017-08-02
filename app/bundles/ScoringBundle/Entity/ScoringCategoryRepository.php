<?php
namespace Mautic\ScoringBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class ScoringCategoryRepository.
 */
class ScoringCategoryRepository extends CommonRepository {
    /**
     * {@inheritdoc}
     */
    public function getEntities($args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias())
            ->from('MauticScoringBundle:ScoringCategory', $this->getTableAlias());

        $args['qb'] = $q;

        return parent::getEntities($args);
    }
    
    /**
     * Get the list of entities, sort by asked criterias
     * @return ScoringCategory[]
     */
    public function getSpecializedList() {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias())
            ->from('MauticScoringBundle:ScoringCategory', $this->getTableAlias())
            ->where($this->getTableAlias().'.isGlobalScore=0') // yeah...
            ->orderBy($this->getTableAlias().'.orderIndex', 'ASC')
            ->addOrderBy($this->getTableAlias().'.name', 'ASC');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias() {
        return 's';
    }
    
    /**
     * Bufferized result - as this computation is heavy and may be called a lot
     * @var array
     */
    protected $usedSomewhere = array();
    
    /**
     * Check a lot of things, to see if the scoreCategory is used anywhere.
     * That method should never be called, as we got Doctrine and Foreign keys
     * But it exists because we need to display a warning at deletion before doing anything else
     * @param integer $id
     * @return boolean
     */
    public function isUsedSomewhere($id) {
        if(!array_key_exists($id, $this->usedSomewhere)) {
            $this->usedSomewhere[$id] = 
                // grab ALL ACTIONS, TRIGGERS, CAMPAIGNS, SEGMENTS
                !empty($this->_em->getRepository('MauticPointBundle:Point')->findByScoreCategory($id))
                ||
                !empty($this->_em->getRepository('MauticPointBundle:Trigger')->findByScoreCategory($id))
                ||
                !empty($this->_em->getRepository('MauticCampaignBundle:Event')->findByScoreCategory($id));
        }
        return $this->usedSomewhere[$id];
    }
}
