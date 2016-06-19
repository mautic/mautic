<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Form Type.
 */
class UserStepType extends AbstractType
{

    /**
     * @var
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $storedData = $this->session->get('mautic.installer.user', new \stdClass());

        $builder->add(
            'firstname',
            'text',
            array(
                'label'       => 'mautic.core.firstname',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array('class' => 'form-control'),
                'required'    => true,
                'data'        => (!empty($storedData->firstname)) ? $storedData->firstname : '',
                'constraints' => array(
                    new Assert\NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'lastname',
            'text',
            array(
                'label'       => 'mautic.core.lastname',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array('class' => 'form-control'),
                'required'    => true,
                'data'        => (!empty($storedData->lastname)) ? $storedData->lastname : '',
                'constraints' => array(
                    new Assert\NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'email',
            'email',
            array(
                'label'       => 'mautic.install.form.user.email',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope'
                ),
                'required'    => true,
                'data'        => (!empty($storedData->email)) ? $storedData->email : '',
                'constraints' => array(
                    new Assert\NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    ),
                    new Assert\Email(
                        array(
                            'message' => 'mautic.core.email.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'username',
            'text',
            array(
                'label'       => 'mautic.install.form.user.username',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class' => 'form-control'
                ),
                'required'    => true,
                'data'        => (!empty($storedData->username)) ? $storedData->username : '',
                'constraints' => array(
                    new Assert\NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'password',
            'password',
            array(
                'label'       => 'mautic.install.form.user.password',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon' => 'fa fa-lock'
                ),
                'required'    => true,
                'constraints' => array(
                    new Assert\NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    ),
                    new Assert\Length(
                        array(
                            'min'        => 6,
                            'minMessage' => 'mautic.install.password.minlength'
                        )
                    )
                )
            )
        );

        $builder->add(
            'buttons',
            'form_buttons',
            array(
                'pre_extra_buttons' => array(
                    array(
                        'name'  => 'next',
                        'label' => 'mautic.install.next.step',
                        'type'  => 'submit',
                        'attr'  => array(
                            'class'   => 'btn btn-success pull-right btn-next',
                            'icon'    => 'fa fa-arrow-circle-right',
                            'onclick' => 'MauticInstaller.showWaitMessage(event);'
                        )
                    )
                ),
                'apply_text'        => '',
                'save_text'         => '',
                'cancel_text'       => ''
            )
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'install_user_step';
    }
}
