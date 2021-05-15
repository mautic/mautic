<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Model\PageModel;

class LoadPageHitData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var PageModel
     */
    private $pageModel;

    public function __construct(PageModel $pageModel)
    {
        $this->pageModel = $pageModel;
    }

    public function load(ObjectManager $manager)
    {
        $hits = CsvHelper::csv_to_array(__DIR__.'/fakepagehitdata.csv');

        foreach ($hits as $rows) {
            $hit = new Hit();
            foreach ($rows as $col => $val) {
                if ('NULL' != $val) {
                    $setter = 'set'.ucfirst($col);
                    if (in_array($col, ['page', 'ipAddress'])) {
                        $hit->$setter($this->getReference($col.'-'.$val));
                    } elseif (in_array($col, ['dateHit', 'dateLeft'])) {
                        $hit->$setter(new \DateTime($val));
                    } elseif ('browserLanguages' == $col) {
                        $val = Serializer::decode(stripslashes($val));
                        $hit->$setter($val);
                    } else {
                        $hit->$setter($val);
                    }
                }
            }
            $this->pageModel->getRepository()->saveEntity($hit);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 8;
    }
}
