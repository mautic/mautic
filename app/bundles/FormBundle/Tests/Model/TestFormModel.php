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

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Tests\FormTestAbstract;

class TestFormModel extends FormTestAbstract
{
    public function testSetFields()
    {
        $form      = new Form();
        $fields    = $this->getTestFormFields();
        $formModel = $this->getFormModel();
        $formModel->setFields($form, $fields);
        $entityFields = $form->getFields()->toArray();
        $this->assertInstanceOf(Field::class, $entityFields[$fields['id']]);
    }

    public function testGetComponentsFields()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('fields', $components);
    }

    public function testGetComponentsActions()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('actions', $components);
    }

    public function testGetComponentsValidators()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('validators', $components);
    }
}
