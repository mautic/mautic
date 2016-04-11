<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FacebookLoginType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class LinkedInLoginType extends AbstractType
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
        $builder->add(
            'width',
            'text',
            array(
                'label_attr' => array('class' => 'control-label'),
                'label'      => 'mautic.integration.LinkedIn.login.width',
                'required'   => false,
                'attr'       => array(
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.integration.LinkedIn.login.width',
                    'preaddon'    => 'fa'
                )
            )
        );
        $builder->add(
            'buttonLabel',
            'text',
            array(
                'label_attr' => array('class' => 'control-label'),
                'label'      => 'mautic.integration.LinkedIn.login.buttonlabel',
                'required'   => false,
                'attr'       => array(
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.integration.LinkedIn.login.buttonlabel',
                    'preaddon'    => 'fa'
                )
            )
        );
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject('LinkedIn');

        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model = $this->factory->getModel('form');

        $integration = array(
            'integration' => $integrationObject->getName(),
        );

        $builder->add(
            'authUrl',
            'hidden',
            array(
                'data' => $model->buildUrl('mautic_integration_auth_user',$integration,true,array()),
            )
        );
        
    }


    /**
     * @return string
     */
    public function getName()
    {
        return "sociallogin_linkedin";
    }
}