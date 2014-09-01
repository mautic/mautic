<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PointActionPageHitType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class PointActionPageHitType extends AbstractType
{

    private $choices;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $viewOther = $factory->getSecurity()->isGranted('page:pages:viewother');
        $choices = $factory->getModel('page')->getRepository()
            ->getPageList('', 0, 0, $viewOther, 'variant');
        foreach ($choices as $page) {
            $this->choices[$page['language']][$page['id']] = $page['title'] . ' (' . $page['alias'] . ')';
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
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

        $builder->add('pages', 'choice', array(
            'choices'       => $this->choices,
            'expanded'      => false,
            'multiple'      => true,
            'label'         => 'mautic.page.point.action.form.pages',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.point.action.form.pages.descr'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "pointaction_pagehit";
    }
}