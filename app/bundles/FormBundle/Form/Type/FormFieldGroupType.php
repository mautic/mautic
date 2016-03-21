<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormFieldGroupType
 */
class FormFieldGroupType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('labelAttributes', 'text', array(
            'label'      => 'mautic.form.field.group.labelattr',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'     => 'form-control',
                'tooltip'   => 'mautic.form.field.help.group.labelattr',
                'maxlength' => '255'
            ),
            'required'   => false
        ));

        if (isset($options['data']['optionlist'])) {
            $data = $options['data']['optionlist'];
        } elseif (isset($options['data']['list'])) {
            // BC support
            $data = array('list' => $options['data']['list']);
        } else {
            $data = array();
        }

        $builder->add('optionlist', 'sortablelist', array(
            'label'      => 'mautic.core.form.list',
            'label_attr' => array('class' => 'control-label'),
            'data'       => $data
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "formfield_group";
    }
}
