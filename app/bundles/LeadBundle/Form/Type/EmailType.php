<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class EmailType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailType extends AbstractType
{

    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('body' => 'html')));

        $builder->add(
            'subject',
            'text',
            array(
                'label'      => 'mautic.email.subject',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false
            )
        );

        $user    = $this->factory->getUser();
        
        $default = (empty($options['data']['fromname'])) ? $user->getFirstName() . ' ' . $user->getLastName() : $options['data']['fromname'];
        $builder->add(
            'fromname', 
            'text', 
             array(
                'label' => 'mautic.lead.email.from_name',
                'label_attr' => array('class' => 'control-label'),
                'attr' => array('class' => 'form-control'),
                'required' => false,
                'data' => $default
            )
        );
        
        $default = (empty($options['data']['from'])) ? $user->getEmail() : $options['data']['from'];
        $builder->add(
            'from',
            'text',
            array(
                'label'      => 'mautic.lead.email.from_email',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false,
                'data'       => $default,
                'constraints' => array(
                    new NotBlank(array(
                        'message' => 'mautic.core.email.required'
                    )),
                    new Email(array(
                        'message' => 'mautic.core.email.required'
                    )),
                )
            )
        );

        $builder->add(
            'body',
            'textarea',
            array(
                'label'      => 'mautic.email.form.body',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'                => 'form-control editor editor-basic-fullpage editor-builder-tokens',
                    'data-token-callback'  => 'email:getBuilderTokens',
                    'data-token-activator' => '{'
                )
            )
        );

        $builder->add('list', 'hidden');

        $builder->add(
            'templates',
            'email_list',
            array(
                'label'      => 'mautic.lead.email.template',
                'label_attr' => array('class' => 'control-label'),
                'required'   => false,
                'attr'       => array(
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.getLeadEmailContent(this)'
                ),
                'multiple'   => false
            )
        );

        $builder->add('buttons', 'form_buttons', array(
            'apply_text'   => false,
            'save_text'    => 'mautic.email.send',
            'save_class'   => 'btn btn-primary',
            'save_icon'    => 'fa fa-send',
            'cancel_icon'  => 'fa fa-times'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "lead_quickemail";
    }
}
