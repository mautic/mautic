<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PointActionFormSubmitType
 */
class PointActionFormSubmitType extends AbstractType
{

    /**
     * @var array
     */
    private $choices = array();

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $viewOther = $factory->getSecurity()->isGranted('form:forms:viewother');
        $choices = $factory->getModel('form')->getRepository()->getFormList('', 0, 0, $viewOther);
        foreach ($choices as $form) {
            $this->choices[$form['id']] = $form['id'] . ':' . $form['name'];
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $default = (empty($options['data']['delta'])) ? 0 : (int) $options['data']['delta'];
        $builder->add('delta', 'number', array(
            'label'      => 'mautic.point.action.delta',
            'label_attr' => array('class' => 'control-label'),
            'attr'       =>
                array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.point.action.delta.help'
                ),
            'precision'  => 0,
            'data'       => $default
        ));

        $builder->add('forms', 'choice', array(
            'choices'       => $this->choices,
            'expanded'      => false,
            'multiple'      => true,
            'label'         => 'mautic.form.point.action.forms',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.point.action.forms.descr'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "pointaction_formsubmit";
    }
}
