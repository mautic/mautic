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
            'label'      => 'mautic.email.send.selectemails',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control chosen',
                'tooltip' => 'mautic.email.send.selectemails_descr'
            ),
            'multiple'   => false,
            'constraints' => array(
                new NotBlank(
                    array('message' => 'mautic.email.chooseemail.notblank')
                )
            )
        ));

        $windowUrl = $this->factory->getRouter()->generate('mautic_email_action', array(
            'objectAction' => 'new',
            'contentOnly'  => 1,
            'updateSelect' => 'campaignevent_properties_email'
        ));

        $builder->add('newEmailButton', 'standalone_button', array(
            'attr'     => array(
                'class'   => 'btn btn-primary',
                'onclick' => 'Mautic.loadNewEmailWindow({
                    "windowUrl": "' . $windowUrl . '"
                })',
                'icon'    => 'fa fa-plus'
            ),
            'label'    => 'mautic.email.send.new.email'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "emailsend_list";
    }
}