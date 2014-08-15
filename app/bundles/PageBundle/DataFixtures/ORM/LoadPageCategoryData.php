<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\PageBundle\Entity\Category;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class LoadPageCategoryData
 *
 * @package Mautic\PageBundle\DataFixtures\ORM
 */
class LoadPageCategoryData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $trans   = $this->container->get('translator');
        $factory = $this->container->get('mautic.factory');
        $repo    = $factory->getModel('page.category')->getRepository();
        $today   = new \DateTime();

        $cat    = new Category();
        $events = $trans->trans('mautic.page.category.events', array(), 'fixtures');

        $cat->setDateAdded($today);
        $cat->setTitle($events);
        $cat->setAlias(strtolower($events));

        $repo->saveEntity($cat);
        $this->setReference('page-cat-1', $cat);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 6;
    }
}