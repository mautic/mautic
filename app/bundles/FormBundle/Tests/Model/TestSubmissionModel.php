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
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Tests\FormAbstractTest;
use Symfony\Component\HttpFoundation\Request;

class TestSubmissionModel extends FormAbstractTest
{
    public function testSaveSubmission()
    {
        $request = new Request();
        $request->setMethod('POST');
        $formData = [
            'var_name_1' => 'value 1',
            'var_name_2' => 'value 2',
            'email'      => 'test@email.com',
            'submit'     => '',
            'formId'     => 1,
            'return'     => '',
            'formName'   => 'testform',
            'formid'     => 1,
        ];
        $post      = $formData;
        $server    = $request->server->all();
        $form      = new Form();
        $fields    = $this->getTestFormFields();
        $formModel = $this->getFormModel();
        $formModel->setFields($form, $fields);

        $submissionModel = $this->getSubmissionModel();
        $this->assertFalse($submissionModel->saveSubmission($post, $server, $form, $request));
        $this->assertInstanceOf(SubmissionEvent::class, $submissionModel->saveSubmission($post, $server, $form, $request, true)['submission']);
    }
}
