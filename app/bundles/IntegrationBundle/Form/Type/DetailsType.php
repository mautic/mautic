<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DetailsType
 *
 * @package Mautic\IntegrationBundle\Form\Type
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
            'label'       => 'mautic.social.form.enabled',
            'empty_value' => false,
            'required'    => false
        ));

        $keys = $options['network_object']->getRequiredKeyFields();
        $builder->add('apiKeys', 'connector_keys', array(
            'label'          => false,
            'required'       => false,
            'connector_keys' => $keys,
            'data'           => $options['data']->getApiKeys()
        ));

        if ($options['network_object']->getAuthenticationType() == 'oauth2') {
            $url      = $options['network_object']->getOAuthLoginUrl();
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
                    'onclick' => 'Mautic.loadAuthModal("' . $url . '", "' . $key . '", "' . $options['network'] . '");'
                ),
                'label'    => 'mautic.social.form.' . $label,
                'disabled' => $disabled
            ));
        }

        //@todo - add event so that other bundles can plug in custom features
        $features = $options['network_object']->getSupportedFeatures();
        if (!empty($features)) {
            $labels = array();
            foreach ($features as $f) {
                $labels[] = 'mautic.social.form.feature.' . $f;
            }
            $builder->add('supportedFeatures', 'choice', array(
                'choice_list' => new ChoiceList($features, $labels),
                'expanded'    => true,
                'label_attr'  => array('class' => 'control-label'),
                'multiple'    => true,
                'label'       => 'mautic.social.form.features',
                'required'    => false
            ));
        }

        $builder->add('featureSettings', 'connector_featuresettings', array(
            'label'          => 'mautic.social.form.feature.settings',
            'required'       => false,
            'data'           => $options['data']->getFeatureSettings(),
            'label_attr'     => array('class' => 'control-label'),
            'network'        => $options['network'],
            'network_object' => $options['network_object'],
            'lead_fields'    => $options['lead_fields']
        ));

        $builder->add('name', 'hidden', array('data' => $options['network']));

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
            'data_class' => 'Mautic\IntegrationBundle\Entity\Connector'
        ));

        $resolver->setRequired(array('network', 'network_object', 'lead_fields'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'connector_details';
    }
}
