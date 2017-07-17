<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadEmailData.
 */
class LoadEmailData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $model  = $this->container->get('mautic.email.model.email');
        $repo   = $model->getRepository();
        $emails = CsvHelper::csv_to_array(__DIR__.'/fakeemaildata.csv');

        foreach ($emails as $count => $rows) {
            $email = new Email();
            $email->setDateAdded(new \DateTime());
            $key = $count + 1;
            foreach ($rows as $col => $val) {
                if ($val != 'NULL') {
                    $setter = 'set'.ucfirst($col);
                    if (in_array($col, ['content', 'variantSettings'])) {
                        $val = unserialize(stripslashes($val));
                    }
                    $email->$setter($val);
                }
            }
            $email->addList($this->getReference('lead-list'));

            $repo->saveEntity($email);
            $this->setReference('email-'.$key, $email);
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 9;
    }
}
