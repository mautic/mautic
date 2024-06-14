<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @extends AbstractType<mixed>
 */
class DateRangeType extends AbstractType
{
    private const DATE_REGEX = '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s([1-9]|[12][0-9]|3[01]),\s\d{4}$/';

    public function __construct(
        private SessionInterface $session,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $humanFormat = 'M j, Y';
        $dateFrom    = $options['data']['date_from'] ?? null;
        $dateTo      = $options['data']['date_to'] ?? null;

        if ($options['set_default_values']) {
            $sessionDateFrom = $this->session->get('mautic.daterange.form.from');
            $sessionDateTo   = $this->session->get('mautic.daterange.form.to');
            if (!empty($sessionDateFrom) && !empty($sessionDateTo)) {
                $defaultFrom = new \DateTime($sessionDateFrom);
                $defaultTo   = new \DateTime($sessionDateTo);
            } else {
                $dateRangeDefault = $this->coreParametersHelper->get('default_daterange_filter', '-1 month');
                $defaultFrom      = new \DateTime($dateRangeDefault);
                $defaultTo        = new \DateTime();
            }

            $dateFrom ??= $defaultFrom->format($humanFormat);
            $dateTo ??= $defaultTo->format($humanFormat);

            $this->session->set('mautic.daterange.form.from', $dateFrom);
            $this->session->set('mautic.daterange.form.to', $dateTo);
        }

        $constraints = [
            new NotBlank(),
            new Regex(
                [
                    'pattern' => self::DATE_REGEX,
                    'match'   => true,
                    'message' => 'mautic.core.daterange.invalid.date.format',
                ]
            ),
        ];

        $builder->add(
            'date_from',
            TextType::class,
            [
                'label'       => 'mautic.core.date.from',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => false,
                'data'        => $dateFrom,
                'constraints' => $constraints,
            ]
        );

        $builder->add(
            'date_to',
            TextType::class,
            [
                'label'       => 'mautic.core.date.to',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => false,
                'data'        => $dateTo,
                'constraints' => $constraints,
            ]
        );

        if ($options['show_apply_button']) {
            $builder->add(
                'apply',
                SubmitType::class,
                [
                    'label' => 'mautic.core.form.apply',
                    'attr'  => ['class' => 'btn btn-default'],
                ]
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_apply_button'  => true,
            'set_default_values' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'daterange';
    }
}
