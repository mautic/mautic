<?php

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPageRedirectsData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $redirects = [
            [
                'alias'       => 'redirect-n-1',
                'redirect_id' => 1,
                'hits'        => 1,
                'unique_hits' => 1,
                'url'         => 'mautic.test',
            ],
        ];

        foreach ($redirects as $redirectConfig) {
            $this->createRedirect($redirectConfig, $manager);
        }
    }

    protected function createRedirect($redirectConfig, ObjectManager $manager)
    {
        $redirect = new Redirect();

        $redirect->setRedirectId($redirectConfig['redirect_id']);
        $redirect->setHits($redirectConfig['hits']);
        $redirect->setUniqueHits($redirectConfig['unique_hits']);
        $redirect->setUrl($redirectConfig['url']);

        $this->setReference($redirectConfig['alias'], $redirect);

        $manager->persist($redirect);
        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 5;
    }
}
