<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DetailsType
 *
 * @package Mautic\AddonBundle\Form\Type
 */
class DetailsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'    => true,
            'label_attr'  => array('class' => 'control-label'),
            'multiple'    => false,
            'label'       => 'mautic.integration.form.enabled',
            'empty_value' => false,
            'required'    => false
        ));

        $keys = $options['integration_object']->getRequiredKeyFields();
        $builder->add('apiKeys', 'integration_keys', array(
            'label'          => false,
            'required'       => false,
            'integration_keys' => $keys,
            'data'           => $options['data']->getApiKeys()
        ));

        if ($options['integration_object']->getAuthenticationType() == 'oauth2') {
            $url      = $options['integration_object']->getOAuthLoginUrl();
            $keys     = $options['data']->getApiKeys();
            $disabled = false;
            $label    = (isset($keys['access_token'])) ? 'reauthorize' : 'authorize';

            //find what key is needed from the URL and pass it to the JS function
            preg_match('/{(.*)}/', $url, $match);
            if (!empty($match[1])) {
                $key = $match[1];
            } else {
                $key = '';
            }

            $builder->add('authButton', 'standalone_button', array(
                'attr'     => array(
                    'class'   => 'btn btn-primary',
                    'onclick' => 'Mautic.loadIntegrationAuthWindow("' . $url . '", "' . $key . '", "' . $options['integration'] . '");'
                ),
                'label'    => 'mautic.integration.form.' . $label,
                'disabled' => $disabled
            ));
        }

        //@todo - add event so that other bundles can plug in custom features
        $features = $options['integration_object']->getSupportedFeatures();
        if (!empty($features)) {
            $labels = array();
            foreach ($features as $f) {
                $labels[] = 'mautic.integration.form.feature.' . $f;
            }
            $builder->add('supportedFeatures', 'choice', array(
                'choice_list' => new ChoiceList($features, $labels),
                'expanded'    => true,
                'label_attr'  => array('class' => 'control-label'),
                'multiple'    => true,
                'label'       => 'mautic.integration.form.features',
                'required'    => false
            ));
        }

        $builder->add('featureSettings', 'integration_featuresettings', array(
            'label'          => 'mautic.integration.form.feature.settings',
            'required'       => false,
            'data'           => $options['data']->getFeatureSettings(),
            'label_attr'     => array('class' => 'control-label'),
            'integration'        => $options['integration'],
            'integration_object' => $options['integration_object'],
            'lead_fields'    => $options['lead_fields']
        ));

        $builder->add('name', 'hidden', array('data' => $options['integration']));

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\AddonBundle\Entity\Integration'
        ));

        $resolver->setRequired(array('integration', 'integration_object', 'lead_fields'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'integration_details';
    }
}
