<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadFormResultData.
 */
class LoadFormResultData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $pageModel = $this->container->get('mautic.page.model.page');
        $repo      = $this->container->get('mautic.form.model.submission')->getRepository();

        $fixture       = &$this;
        $importResults = function ($results) use ($pageModel, $repo, &$fixture) {
            foreach ($results as $count => $rows) {
                $submission = new Submission();
                $submission->setDateSubmitted(new \DateTime());

                foreach ($rows as $col => $val) {
                    if ($val != 'NULL') {
                        $setter = 'set'.\ucfirst($col);
                        if (\in_array($col, ['form', 'page', 'ipAddress', 'lead'])) {
                            if ($col === 'lead') {
                                // For some reason the lead must be linked with id - 1
                                $entity = $fixture->getReference($col.'-'.($val - 1));
                            } else {
                                $entity = $fixture->getReference($col.'-'.$val);
                            }
                            if ($col == 'page') {
                                $submission->setReferer($pageModel->generateUrl($entity));
                            }
                            $submission->$setter($entity);
                            unset($rows[$col]);
                        } else {
                            //the rest are custom field values
                            break;
                        }
                    }
                }

                $submission->setResults($rows);
                $repo->saveEntity($submission);
            }
        };

        $results = CsvHelper::csv_to_array(__DIR__.'/fakeresultdata.csv');
        $importResults($results);

        \sleep(2);

        $results2 = CsvHelper::csv_to_array(__DIR__.'/fakeresult2data.csv');
        $importResults($results2);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 9;
    }
}
