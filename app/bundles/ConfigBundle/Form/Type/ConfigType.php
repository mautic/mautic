<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\StringToDatetimeTransformer;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 */
class ConfigType extends AbstractType
{

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data'] as $bundle => $config) {
            foreach ($config as $key => $value) {
                if (in_array($key, array('api_enabled', 'cat_in_page_url'))) {
                    $builder->add($key, 'button_group', array(
                        'choice_list' => new ChoiceList(
                            array(false, true),
                            array('mautic.core.form.no', 'mautic.core.form.yes')
                        ),
                        'label'       => 'mautic.config.' . $bundle . '.' . $key,
                        'expanded'    => true,
                        'empty_value' => false,
                        'data'        => (bool) $value
                    ));
                } elseif (in_array($key, array('api_mode', 'locale', 'theme', 'ip_lookup_service', 'mailer_spool_type'))) {
                    switch ($key) {
                        case 'api_mode':
                            $choices = array(
                                'oauth1' => 'mautic.api.config.oauth1',
                                'oauth2' => 'mautic.api.config.oauth2'
                            );
                            break;
                        case 'locale':
                            $choices = $this->factory->getParameter('supported_languages');
                            break;
                        case 'theme':
                            $choices = $this->factory->getInstalledThemes();
                            break;
                        case 'ip_lookup_service':
                            // TODO - Write an API endpoint listing our supported services and build this list from that
                            $choices = array(
                                'telize'            => 'mautic.core.config.ip_lookup_service.telize',
                                'freegeoip'         => 'mautic.core.config.ip_lookup_service.freegeoip',
                                'geobytes'          => 'mautic.core.config.ip_lookup_service.geobytes',
                                'ipinfodb'          => 'mautic.core.config.ip_lookup_service.ipinfodb',
                                'geoips'            => 'mautic.core.config.ip_lookup_service.geoips',
                                'maxmind_country'   => 'mautic.core.config.ip_lookup_service.maxmind_country',
                                'maxmind_precision' => 'mautic.core.config.ip_lookup_service.maxmind_precision',
                                'maxmind_omni'      => 'mautic.core.config.ip_lookup_service.maxmind_omni'
                            );
                            break;
                        case 'mailer_spool_type':
                            $choices = array(
                                'file'   => 'mautic.core.config.mailer_spool_type.file',
                                'memory' => 'mautic.core.config.mailer_spool_type.memory'
                            );
                            break;
                    }

                    $builder->add($key, 'choice', array(
                        'choices'  => $choices,
                        'label'    => 'mautic.config.' . $bundle . '.' . $key,
                        'required' => false,
                        'attr'     => array(
                            'class' => 'form-control'
                        ),
                        'data'     => $value
                    ));
                } elseif ($key == 'default_timezone') {
                    $builder->add($key, 'timezone', array(
                        'label'       => 'mautic.config.' . $bundle . '.' . $key,
                        'label_attr'  => array('class' => 'control-label'),
                        'attr'        => array(
                            'class' => 'form-control'
                        ),
                        'multiple'    => false,
                        'empty_value' => 'mautic.user.user.form.defaulttimezone',
                        'data'        => $value
                    ));
                } elseif (in_array($key, array('mailer_password', 'transifex_password'))) {
                    $builder->add($key, 'password', array(
                        'label'      => 'mautic.config.' . $bundle . '.' . $key,
                        'label_attr' => array('class' => 'control-label'),
                        'attr'       => array(
                            'class'       => 'form-control',
                            'placeholder' => 'mautic.user.user.form.passwordplaceholder',
                            'preaddon'    => 'fa fa-lock'
                        ),
                        'data'       => $value
                    ));
                } else {
                    if (in_array($key, array('mailer_from_email'))) {
                        $type = 'email';
                    } else {
                        $type = 'text';
                    }

                    $builder->add($key, $type, array(
                        'label'      => 'mautic.config.' . $bundle . '.' . $key,
                        'label_attr' => array('class' => 'control-label'),
                        'attr'       => array('class' => 'form-control'),
                        'data'       => $value
                    ));
                }
            }
        }

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config';
    }
}
