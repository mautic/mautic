<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @extends AbstractType<mixed>
 */
class DateRangeType extends AbstractType
{
    public function __construct(
        private RequestStack $requestStack,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session         = $this->requestStack->getSession();
        $humanFormat     = 'M j, Y';
        $sessionDateFrom = $session->get('mautic.daterange.form.from');
        $sessionDateTo   = $session->get('mautic.daterange.form.to');
        if (!empty($sessionDateFrom) && !empty($sessionDateTo)) {
            $defaultFrom = new \DateTime($sessionDateFrom);
            $defaultTo   = new \DateTime($sessionDateTo);
        } else {
            $dateRangeDefault = $this->coreParametersHelper->get('default_daterange_filter', '-1 month');
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
            TextType::class,
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
            TextType::class,
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
            SubmitType::class,
            [
                'label' => 'mautic.core.form.apply',
                'attr'  => ['class' => 'btn btn-ghost btn-sm'],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $session->set('mautic.daterange.form.from', $dateFrom->format($humanFormat));
        $session->set('mautic.daterange.form.to', $dateTo->format($humanFormat));
    }

    public function getBlockPrefix(): string
    {
        return 'daterange';
    }
}
