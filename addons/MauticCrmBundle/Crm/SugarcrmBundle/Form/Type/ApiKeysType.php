<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ApiKeysType
 *
 * @package MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Form\Type
 */
class ApiKeysType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('url', 'text',array(
            'label'      => 'mautic.sugarcrm.form.url',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('client_key', 'text',array(
            'label'      => 'mautic.sugarcrm.form.clientkey',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('client_secret', 'text',array(
            'label'      => 'mautic.sugarcrm.form.clientsecret',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('username', 'text',array(
            'label'      => 'mautic.sugarcrm.form.username',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('password', 'text',array(
            'label'      => 'mautic.sugarcrm.form.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "sugarcrm_apikeys";
    }
}