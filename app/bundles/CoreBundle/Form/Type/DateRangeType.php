<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FilterType.
 */
class DateRangeType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $humanFormat     = 'M j, Y';
        $session         = $this->factory->get('session');
        $sessionDateFrom = $session->get('mautic.daterange.form.from');
        $sessionDateTo   = $session->get('mautic.daterange.form.to');
        if (!empty($sessionDateFrom) && !empty($sessionDateTo)) {
            $defaultFrom = new \DateTime($sessionDateFrom);
            $defaultTo   = new \DateTime($sessionDateTo);
        } else {
            $dateRangeDefault = $this->factory->getParameter('default_daterange_filter', '-1 month');
            $defaultFrom      = new \DateTime($dateRangeDefault);
            $defaultTo        = new \DateTime();
        }

        $dateFrom = (empty($options['data']['date_from']))
            ?
            $defaultFrom
            :
            new \DateTime($options['data']['date_from']);

        $builder->add(
            'date_from',
            'text',
            [
                'label'      => 'mautic.core.date.from',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'data'       => $dateFrom->format($humanFormat),
            ]
        );

        $dateTo = (empty($options['data']['date_to']))
            ?
            $defaultTo
            :
            new \DateTime($options['data']['date_to']);

        $builder->add(
            'date_to',
            'text',
            [
                'label'      => 'mautic.core.date.to',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'data'       => $dateTo->format($humanFormat),
            ]
        );

        $builder->add(
            'apply',
            'submit',
            [
                'label' => 'mautic.core.form.apply',
                'attr'  => ['class' => 'btn btn-default'],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $session->set('mautic.daterange.form.from', $dateFrom->format($humanFormat));
        $session->set('mautic.daterange.form.to', $dateTo->format($humanFormat));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'daterange';
    }
}
