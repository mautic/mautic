<?php
namespace Mautic\ScoringBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\ScoringBundle\Entity\ScoringCategory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of LoadScoringCategoryData
 *
 * @author captivea-qch
 */
class LoadScoringCategoryData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {
    const REFKEY = 'scoringcategory-global';
    
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {
        $factory  = $this->container->get('mautic.factory');
        $scoringRepo = $factory->getModel('scoring.scoringCategory')->getRepository();
        
        $globalScoring = new ScoringCategory;
        $globalScoring->setName($this->container->get('translator')->trans('mautic.scoring.scoringCategory.globalscore.name'));
        $globalScoring->setIsGlobalScore(true);
        $globalScoring->setGlobalScoreModifier(0.00);
        $globalScoring->setOrderIndex(-1);
        $globalScoring->setUpdateGlobalScore(false);
        $scoringRepo->saveEntity($globalScoring);
        
        $this->setReference(static::REFKEY, $globalScoring); // keep it; we may need to update entire database to get that to work
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 150;
    }
}
