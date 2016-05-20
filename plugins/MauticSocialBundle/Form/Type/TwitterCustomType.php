<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use \Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use MauticPlugin\MauticSocialBundle\Form\Type\TwitterAbstractType;

class TwitterCustomType extends TwitterAbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('custom', 'text', array(
            'label'      => 'mautic.social.monitoring.twitter.custom',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.social.monitoring.twitter.custom.tooltip',
                'preaddon'    => 'fa fa-crosshairs'
            )
        ));

        // pull in the parent type's form builder
        parent::buildForm($builder, $options);
    }

    public function getName()
    {
        return "twitter_custom";
    }
}