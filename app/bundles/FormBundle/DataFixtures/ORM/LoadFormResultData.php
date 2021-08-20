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
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\PageBundle\Model\PageModel;

class LoadFormResultData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var PageModel
     */
    private $pageModel;

    /**
     * @var SubmissionModel
     */
    private $submissionModel;

    /**
     * {@inheritdoc}
     */
    public function __construct(PageModel $pageModel, SubmissionModel $submissionModel)
    {
        $this->pageModel       = $pageModel;
        $this->submissionModel = $submissionModel;
    }

    public function load(ObjectManager $manager)
    {
        $importResults = function ($results) {
            foreach ($results as $rows) {
                $submission = new Submission();
                $submission->setDateSubmitted(new \DateTime());

                foreach ($rows as $col => $val) {
                    if ('NULL' != $val) {
                        $setter = 'set'.\ucfirst($col);
                        if (\in_array($col, ['form', 'page', 'ipAddress', 'lead'])) {
                            if ('lead' === $col) {
                                // For some reason the lead must be linked with id - 1
                                $entity = $this->getReference($col.'-'.($val - 1));
                            } else {
                                $entity = $this->getReference($col.'-'.$val);
                            }
                            if ('page' == $col) {
                                $submission->setReferer($this->pageModel->generateUrl($entity));
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
                $this->submissionModel->getRepository()->saveEntity($submission);
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
