<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;

/**
 * After a successful submission, the focus item hides the form and shows the form's message in
 * the message element. Without that element, the focus item is left empty.
 */
final class FocusFormSuccessMessageTest extends MauticMysqlTestCase
{
    public function testFormInFocusItemHasMessageElements(): void
    {
        $form = new Form();
        $form->setName('Focus Sign-up');
        $form->setAlias('focussignup');
        $form->setPostAction('message');
        $form->setPostActionProperty('Thank you!');

        $emailField = new Field();
        $emailField->setLabel('Email');
        $emailField->setType('email');
        $emailField->setAlias('email');
        $emailField->setForm($form);
        $emailField->setOrder(1);
        $emailField->setMappedObject('contact');
        $emailField->setMappedField('email');
        $form->addField('email', $emailField);

        $this->em->persist($form);
        $this->em->flush();

        $focus = new Focus();
        $focus->setName('Focus with form');
        $focus->setType('form');
        $focus->setStyle('modal');
        $focus->setForm($form);
        $focus->setHtmlMode('basic');
        $focus->setProperties([
            'modal'   => ['placement' => 'middle'],
            'colors'  => ['primary' => '3498db', 'text' => '000000', 'button' => '3498db', 'button_text' => 'ffffff'],
            'content' => ['headline' => 'Sign up', 'font' => 'Arial, sans-serif'],
        ]);

        $this->em->persist($focus);
        $this->em->flush();

        /** @var FocusModel $focusModel */
        $focusModel = self::getContainer()->get(FocusModel::class);
        $formHtml   = (string) $focusModel->getContent($focus->toArray())['form'];

        // The focus item's MauticFocusHandler writes into these elements (default setting: "top").
        $message = strpos($formHtml, 'id="mauticform_focussignup_focus_message"');
        $error   = strpos($formHtml, 'id="mauticform_focussignup_focus_error"');
        $fields  = strpos($formHtml, 'class="mauticform-innerform"');

        $this->assertNotFalse($message, 'The form in the focus item has no message element.');
        $this->assertNotFalse($error, 'The form in the focus item has no error element.');
        $this->assertLessThan($fields, $message, 'The message element is not above the fields.');
    }
}
