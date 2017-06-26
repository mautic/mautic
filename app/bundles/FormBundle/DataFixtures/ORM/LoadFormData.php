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
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadFormData.
 */
class LoadFormData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $model        = $this->container->get('mautic.form.model.form');
        $repo         = $model->getRepository();
        $forms        = CsvHelper::csv_to_array(__DIR__.'/fakeformdata.csv');
        $formEntities = [];
        foreach ($forms as $count => $rows) {
            $form = new Form();
            $key  = $count + 1;
            foreach ($rows as $col => $val) {
                if ($val != 'NULL') {
                    $setter = 'set'.ucfirst($col);

                    if (in_array($col, ['dateAdded'])) {
                        $form->$setter(new \DateTime($val));
                    } elseif (in_array($col, ['cachedHtml'])) {
                        $val = stripslashes($val);
                        $form->$setter($val);
                    } else {
                        $form->$setter($val);
                    }
                }
            }
            $repo->saveEntity($form);
            $formEntities[] = $form;
            $this->setReference('form-'.$key, $form);
        }

        //import fields
        $fields = CsvHelper::csv_to_array(__DIR__.'/fakefielddata.csv');
        $repo   = $this->container->get('mautic.form.model.field')->getRepository();
        foreach ($fields as $count => $rows) {
            $field = new Field();
            foreach ($rows as $col => $val) {
                if ($val != 'NULL') {
                    $setter = 'set'.ucfirst($col);

                    if (in_array($col, ['form'])) {
                        $form = $this->getReference('form-'.$val);
                        $field->$setter($form);
                        $form->addField($count, $field);
                    } elseif (in_array($col, ['customParameters', 'properties'])) {
                        $val = unserialize(stripslashes($val));
                        $field->$setter($val);
                    } else {
                        $field->$setter($val);
                    }
                }
            }
            $repo->saveEntity($field);
        }

        //import actions
        $actions = CsvHelper::csv_to_array(__DIR__.'/fakeactiondata.csv');
        $repo    = $this->container->get('mautic.form.model.action')->getRepository();
        foreach ($actions as $count => $rows) {
            $action = new Action();
            foreach ($rows as $col => $val) {
                if ($val != 'NULL') {
                    $setter = 'set'.ucfirst($col);

                    if (in_array($col, ['form'])) {
                        $action->$setter($this->getReference('form-'.$val));
                    } elseif (in_array($col, ['properties'])) {
                        $val = unserialize(stripslashes($val));
                        if ($col == 'settings') {
                            $val['callback'] = stripslashes($val['callback']);
                        }

                        $action->$setter($val);
                    } else {
                        $action->$setter($val);
                    }
                }
            }
            $repo->saveEntity($action);
        }

        //create the tables
        foreach ($formEntities as $form) {
            //create the HTML
            $model->generateHtml($form);

            //create the schema
            $model->createTableSchema($form, true, true);
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
