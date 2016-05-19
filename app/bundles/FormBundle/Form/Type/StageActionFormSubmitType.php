<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class StageActionFormSubmitType
 */
class StageActionFormSubmitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('forms', 'form_list', array(
            'label'         => 'mautic.form.stage.action.forms',
            'label_attr'    => array('class' => 'control-label'),
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.stage.action.forms.descr'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "stageaction_formsubmit";
    }
}
