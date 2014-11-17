<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SocialMediaConfigType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class ConfigType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('services', 'socialmedia_services', array(
            'label'        => false,
            'integrations' => $options['integrations'],
            'lead_fields'  => $options['lead_fields'],
            'data'         => $options['data']['services']
        ));

        $builder->add('buttons', 'form_buttons', array(
            'apply_text'  => 'mautic.core.form.save',
            'apply_icon'  => 'fa fa-save',
            'save_text'   => false,
            'cancel_text' => false
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('integrations', 'lead_fields'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "socialmedia_config";
    }
}