<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ReportType
 *
 * @package Mautic\ReportBundle\Form\Type
 */
class ReportType extends AbstractType
{

    private $translator;
    private $themes;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->themes     = $factory->getInstalledThemes('report');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        //$builder->addEventSubscriber(new FormExitSubscriber('report.report', $options));

        $builder->add('title', 'text', array(
            'label'      => 'mautic.report.report.form.title',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.core.form.ispublished',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            //'data_class' => 'Mautic\ReportBundle\Entity\Report'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "report";
    }
}
