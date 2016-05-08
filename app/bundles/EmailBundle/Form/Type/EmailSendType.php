<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class EmailSendType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailSendType extends AbstractType
{
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', 'email_list', array(
            'label'       => 'mautic.email.send.selectemails',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.choose.emails_descr',
                'onchange'=> 'Mautic.disabledEmailAction()'
            ),
            'multiple'    => false,
            'constraints' => array(
                new NotBlank(
                    array('message' => 'mautic.email.chooseemail.notblank')
                )
            )
        ));

        if (!empty($options['update_select'])) {
            $windowUrl = $this->factory->getRouter()->generate('mautic_email_action', array(
                'objectAction' => 'new',
                'contentOnly'  => 1,
                'updateSelect' => $options['update_select']
            ));

            $builder->add('newEmailButton', 'button', array(
                'attr'  => array(
                    'class'   => 'btn btn-primary btn-nospin',
                    'onclick' => 'Mautic.loadNewEmailWindow({
                        "windowUrl": "' . $windowUrl . '"
                    })',
                    'icon'    => 'fa fa-plus'
                ),
                'label' => 'mautic.email.send.new.email'
            ));

            $email = $options['data']['email'];

            // create button edit email
            $windowUrlEdit = $this->factory->getRouter()->generate('mautic_email_action', array(
                'objectAction' => 'edit',
                'objectId'     => 'emailId',
                'contentOnly'  => 1,
                'updateSelect' => $options['update_select']
            ));

            $builder->add('editEmailButton', 'button', array(
                'attr'  => array(
                    'class'     => 'btn btn-primary btn-nospin',
                    'onclick'   => 'Mautic.loadNewEmailWindow(Mautic.standardEmailUrl({"windowUrl": "' . $windowUrlEdit . '"}))',
                    'disabled'  => !isset($email),
                    'icon'      => 'fa fa-edit'
                ),
                'label' => 'mautic.email.send.edit.email'
            ));

            // create button preview email
            $windowUrlPreview = $this->factory->getRouter()->generate('mautic_email_preview', array('objectId' => 'emailId'));

            $builder->add('previewEmailButton', 'button', array(
                'attr'  => array(
                    'class'     => 'btn btn-primary btn-nospin',
                    'onclick'   => 'Mautic.loadNewEmailWindow(Mautic.standardEmailUrl({"windowUrl": "' . $windowUrlPreview . '"}))',
                    'disabled'  => !isset($email),
                    'icon'      => 'fa fa-external-link'
                ),
                'label' => 'mautic.email.send.preview.email'
            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('update_select'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "emailsend_list";
    }
}
