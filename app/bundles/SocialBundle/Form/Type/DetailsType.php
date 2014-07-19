<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaDetailsType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class DetailsType extends AbstractType
{

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'label'         => 'mautic.social.form.enabled',
            'empty_value'   => false,
            'required'      => false
        ));

        $keys = $options['sm_object']->getRequiredKeyFields();
        $builder->add('apiKeys', 'socialmedia_keys', array(
            'label'    => false,
            'required' => false,
            'sm_keys'  => $keys
        ));

        if ($options['sm_object']->getAuthenticationType() == 'oauth2') {
            $url      = $options['sm_object']->getOAuthLoginUrl();
            $keys     = $options['data']->getApiKeys();
            $disabled = false;
            $label    = (isset($keys['access_token'])) ? 'reauthorize' : 'authorize';
            if (empty($keys['clientId']) || empty($keys['clientSecret'])) {
                $disabled = true;
                $builder->add('notice', 'spacer', array(
                    'text' => 'mautic.social.form.savefirst',
                    'class' => 'text-danger',
                    ''
                ));
            }
            $builder->add('authButton', 'standalone_button', array(
                'attr'     => array(
                    'class'   => 'btn btn-primary',
                    'onclick' => 'window.location="' . $url . '";'
                ),
                'label'    => 'mautic.social.form.' . $label,
                'disabled' => $disabled
            ));
        }

        $features = $options['sm_object']->getSupportedFeatures();
        if (!empty($features)) {
            $labels = array();
            foreach ($features as $f) {
                $labels[] = 'mautic.social.form.feature.' . $f;
            }
            $builder->add('supportedFeatures', 'choice', array(
                'choice_list'   => new ChoiceList($features, $labels),
                'expanded'      => true,
                'label_attr'    => array('class' => 'control-label'),
                'multiple'      => true,
                'label'         => 'mautic.social.form.features',
                'required'      => false
            ));
        }

        $fields = NetworkIntegrationHelper::getAvailableFields($this->factory, $options['sm_service']);
        if (!empty($fields)) {
            $builder->add('leadFields', 'socialmedia_fields', array(
                'label'       => 'mautic.social.fieldassignments',
                'required'    => false,
                'lead_fields' => $options['lead_fields'],
                'sm_fields'   => $fields
            ));
        }

        $builder->add('name', 'hidden', array('data' => $options['sm_service']));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\SocialBundle\Entity\SocialNetwork'
        ));

        $resolver->setRequired(array('sm_service', 'sm_object', 'lead_fields'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_details";
    }
}