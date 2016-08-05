<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Class SubmitActionEmailType
 */
class SubmitActionEmailType extends AbstractType
{

    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = (isset($options['data']['subject'])) ? $options['data']['subject'] : $this->factory->getTranslator()->trans('mautic.form.action.sendemail.subject.default');
        $builder->add('subject', 'text', array(
            'label'      => 'mautic.form.action.sendemail.subject',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false,
            'data'       => $data
        ));

        if (!isset($options['data']['message'])) {
            $fields  = $this->factory->getModel('form.field')->getSessionFields($options['attr']['data-formid']);
            $message = '';

            foreach ($fields as $f) {
                if (in_array($f['type'], array('button', 'freetext', 'captcha')))
                    continue;

                $message .= "<strong>{$f['label']}</strong>: {formfield={$f['alias']}}<br />";
            }
        } else {
            $message = $options['data']['message'];
        }

        $builder->add('message', 'textarea', array(
            'label'      => 'mautic.form.action.sendemail.message',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor editor-basic'),
            'required'   => false,
            'data'       => $message
        ));

        $default = (isset($options['data']['copy_lead'])) ? $options['data']['copy_lead'] : true;
        $builder->add('copy_lead', 'yesno_button_group', array(
            'label'       => 'mautic.form.action.sendemail.copytolead',
            'data'        => $default
        ));

        $builder->add('to', 'text', array(
            'label'      => 'mautic.form.action.sendemail.to',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.core.optional',
                'tooltip'     => 'mautic.form.action.sendemail.multiple.emails'
            ),
            'required'   => false,
            'constraints' => new Email(array(
                'message' => 'mautic.core.email.required'
            ))
        ));

        $builder->add('cc', 'text', array(
            'label'      => 'mautic.form.action.sendemail.cc',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.core.optional',
                'tooltip'     => 'mautic.form.action.sendemail.multiple.emails'
            ),
            'required'   => false,
            'constraints' => new Email(array(
                'message' => 'mautic.core.email.required'
            ))
        ));

        $builder->add('bcc', 'text', array(
            'label'      => 'mautic.form.action.sendemail.bcc',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.core.optional',
                'tooltip'     => 'mautic.form.action.sendemail.multiple.emails'
            ),
            'required'   => false,
            'constraints' => new Email(array(
                'message' => 'mautic.core.email.required'
            ))
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "form_submitaction_sendemail";
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $fields = $this->factory->getModel('form.field')->getSessionFields($options['attr']['data-formid']);

        $choices = array();

        foreach ($fields as $f) {
            if (in_array($f['type'], array('button', 'freetext', 'captcha')))
                continue;

            $token = '{formfield=' . $f['alias'] . '}';
            $choices[$token] = $f['label'];
        }

        $view->vars['formFields'] = $choices;
    }
}
