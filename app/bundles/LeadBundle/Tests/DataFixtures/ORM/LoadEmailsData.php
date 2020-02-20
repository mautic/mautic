<?php

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadEmailsData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $emails = [
            [
                'alias'   => 'email-n-1',
                'name'    => 'Test Clicked any link from any email',
                'subject' => 'Clicked any link from any email',
            ],
        ];

        foreach ($emails as $emailConfig) {
            $this->createEmail($emailConfig, $manager);
        }
    }

    protected function createEmail($emailConfig, ObjectManager $manager)
    {
        $email = new Email();

        $email->setName($emailConfig['name']);
        $email->setSubject($emailConfig['subject']);

        $this->setReference($emailConfig['alias'], $email);

        $manager->persist($email);
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
