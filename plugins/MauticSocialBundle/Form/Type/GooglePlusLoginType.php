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
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'annotation',
            'choice',
            array(
                'choices'     => array(
                    'inline'          => 'mautic.integration.GooglePlus.share.annotation.inline',
                    'bubble'          => 'mautic.integration.GooglePlus.share.annotation.bubble',
                    'vertical-bubble' => 'mautic.integration.GooglePlus.share.annotation.verticalbubble',
                    'none'            => 'mautic.integration.GooglePlus.share.annotation.none'
                ),
                'label'       => 'mautic.integration.GooglePlus.share.annotation',
                'required'    => false,
                'empty_value' => false,
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array('class' => 'form-control')
            )
        );

        $builder->add(
            'height',
            'choice',
            array(
                'choices'     => array(
                    ''   => 'mautic.integration.GooglePlus.share.height.standard',
                    '15' => 'mautic.integration.GooglePlus.share.height.small',
                    '24' => 'mautic.integration.GooglePlus.share.height.large',

                ),
                'label'       => 'mautic.integration.GooglePlus.share.height',
                'required'    => false,
                'empty_value' => false,
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array('class' => 'form-control')
            )
        );

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject('GooglePlus');


        $keys     = $integrationObject->getDecryptedApiKeys();
        $clientId = $keys[$integrationObject->getClientIdKey()];

        $builder->add(
            'clientId',
            'hidden',
            array(
                'data' => $clientId,
            )
        );

        $mappedLeadFields = $integrationObject->getAvailableLeadFields();
        $socialFields     = '';

        foreach ($mappedLeadFields as $key => $field) {
            $socialFields .= $key.",";
        }

        $builder->add(
            'socialProfile',
            'hidden',
            array(

                'data' => substr($socialFields, 0, -1),
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