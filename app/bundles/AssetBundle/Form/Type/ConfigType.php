<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class ConfigType extends AbstractType
{   
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $constraints = array(
            'required' => array(new NotBlank(array('message' => 'mautic.core.required')))
        );

        $builder->add('upload_dir', 'text', array(
            'label'       => 'mautic.asset.config.form.upload.dir',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class' => 'form-control'),
            'constraints' => $constraints['required'],
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'assetconfig';
    }
}