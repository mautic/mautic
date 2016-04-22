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
class GooglePlusLoginType extends AbstractType
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
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject('GooglePlus');
        $disabled = true;

        if ($integrationObject->getIntegrationSettings()->isPublished()) {

            /** @var \Mautic\AssetBundle\Model\AssetModel $model */
            $model = $this->factory->getModel('form');

            $integration = array(
                'integration' => $integrationObject->getName(),
            );

            $builder->add(
                'authUrl',
                'hidden',
                array(
                    'data' => $model->buildUrl('mautic_integration_auth_user', $integration, true, array()),
                )
            );
            $disabled = false;
        }

        $builder->add(
            'width',
            'text',
            array(
                'label_attr' => array('class' => 'control-label'),
                'label' => 'mautic.integration.GooglePlus.login.width',
                'required' => false,
                'disabled' => $disabled,
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'mautic.integration.GooglePlus.login.width',
                    'preaddon' => 'fa'
                )
            )
        );
        $builder->add(
            'buttonLabel',
            'text',
            array(
                'label_attr' => array('class' => 'control-label'),
                'label' => 'mautic.integration.GooglePlus.login.buttonlabel',
                'required' => false,
                'disabled' => $disabled,
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'mautic.integration.GooglePlus.login.buttonlabel',
                    'preaddon' => 'fa'
                )
            )
        );
    }


    /**
     * @return string
     */
    public function getName()
    {
        return "sociallogin_googleplus";
    }
}