<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Test;

use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestFormModel extends WebTestCase
{
    public function testGenerateFieldColumns()
    {
        $columns = ['name' => 'fieldvalue', 'type' => 'typevalue'];
        $fields  =
            [
                'label'        => 'name',
                'showLabel'    => 1,
                'saveResult'   => 1,
                'defaultValue' => false,
            ];

        $formModel = $this->getMockBuilder(FormModel::class)->disableOriginalConstructor()->getMock(); //this needs to be set with constructor args

        $formEntity = new Form();
        $formModel->setFields($formEntity, $fields);
        $this->assertEquals($columns, $formModel->generateFieldColumns($formEntity));
    }
}
