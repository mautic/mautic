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
     * {@inheritdoc}
     */
    public function getTableAlias() {
        return 's';
    }
}
