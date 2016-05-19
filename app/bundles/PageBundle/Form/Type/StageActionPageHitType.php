<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class StageActionPageHitType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class StageActionPageHitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('pages', 'page_list', array(
            'label'         => 'mautic.page.stage.action.form.pages',
            'label_attr'    => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.stage.action.form.pages.descr'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'stageaction_pagehit';
    }
}
