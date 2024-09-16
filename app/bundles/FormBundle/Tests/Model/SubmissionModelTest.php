<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Tests\FormTestAbstract;
use Symfony\Component\HttpFoundation\Request;

class SubmissionModelTest extends FormTestAbstract
{
    public function testSaveSubmission()
    {
        $request = new Request();
        $request->setMethod('POST');
        $formData = [
            'var_name_1' => 'value 1',
            'var_name_2' => 'value 2',
            'email'      => 'test@email.com',
            'file'       => 'test.jpg',
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
        /** @var SubmissionEvent $submissionEvent */
        $submissionEvent = $submissionModel->saveSubmission($post, $server, $form, $request, true)['submission'];
        $this->assertInstanceOf(SubmissionEvent::class, $submissionEvent);
        $alias              = 'email';
        $token              = '{formfield='.$alias.'}';
        $tokens[$token]     = $formData[$alias];
        $this->assertEquals($tokens[$token], $submissionEvent->getTokens()[$token]);

        $alias              = $this->getTestFormFields()['file']['alias'];
        $token              = '{formfield='.$alias.'}';
        $tokens[$token]     = $formData[$alias];
        $this->assertNotEquals($tokens[$token], $submissionEvent->getTokens()[$token]);

        $this->assertFalse($submissionModel->saveSubmission($post, $server, $form, $request));
    }

    public function testNormalizeValues()
    {
        $submissionModel     = $this->getSubmissionModel();
        $reflection          = new \ReflectionClass(SubmissionModel::class);
        $method              = $reflection->getMethod('normalizeValue');
        $method->setAccessible(true);

        $field = new Field();
        $this->assertEquals('', $method->invokeArgs($submissionModel, ['', $field]));
        $this->assertEquals(1, $method->invokeArgs($submissionModel, [1, $field]));
        $this->assertEquals('1, 2', $method->invokeArgs($submissionModel, [[1, 2], $field]));

        // field wiht list
        $field = new Field();
        $field->setProperties(
            [
                'list' => [
                        'list' => [
                                [
                                    'label' => 'First',
                                    'value' => 1,
                                ],
                                [
                                    'label' => 'Second',
                                    'value' => 2,
                                ],
                            ],
                    ],
            ]
        );
        $this->assertEquals('', $method->invokeArgs($submissionModel, ['', $field]));
        $this->assertEquals('First', $method->invokeArgs($submissionModel, [1, $field]));
        $this->assertEquals('First, Second', $method->invokeArgs($submissionModel, [[1, 2], $field]));
    }
}
