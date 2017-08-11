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

use Symfony\Component\Form\Extension\Core\DataTransformer\ArrayToPartsTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as SymfonyDateTime;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FilterType.
 */
class DateTimeType extends SymfonyDateTime
{
    private static $acceptedFormats = [
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $parts     = ['year', 'month', 'day', 'hour'];
        $dateParts = ['year', 'month', 'day'];
        $timeParts = ['hour'];

        if ($options['with_minutes']) {
            $parts[]     = 'minute';
            $timeParts[] = 'minute';
        }

        if ($options['with_seconds']) {
            $parts[]     = 'second';
            $timeParts[] = 'second';
        }

        $dateFormat = is_int($options['date_format']) ? $options['date_format'] : self::DEFAULT_DATE_FORMAT;
        $timeFormat = self::DEFAULT_TIME_FORMAT;
        $calendar   = \IntlDateFormatter::GREGORIAN;
        $pattern    = is_string($options['format']) ? $options['format'] : null;

        if (!in_array($dateFormat, self::$acceptedFormats, true)) {
            throw new InvalidOptionsException('The "date_format" option must be one of the IntlDateFormatter constants (FULL, LONG, MEDIUM, SHORT) or a string representing a custom format.');
        }

        if ('single_text' === $options['widget']) {
            if (self::HTML5_FORMAT === $pattern) {
                $builder->addViewTransformer(new DateTimeToRfc3339Transformer(
                    $options['model_timezone'],
                    $options['view_timezone']
                ));
            } else {
                $builder->addViewTransformer(new DateTimeToLocalizedStringTransformer(
                    $options['model_timezone'],
                    $options['view_timezone'],
                    $dateFormat,
                    $timeFormat,
                    $calendar,
                    $pattern
                ));
            }
        } else {
            // Only pass a subset of the options to children
            $dateOptions = array_intersect_key($options, array_flip([
                'years',
                'months',
                'days',
                'empty_value',
                'placeholder',
                'choice_translation_domain',
                'required',
                'translation_domain',
                'html5',
                'invalid_message',
                'invalid_message_parameters',
                'date_attr', // <= attr for date widget
            ]));
            if (isset($dateOptions['date_attr'])) {
                $ov = $dateOptions['date_attr'];
                unset($dateOptions['date_attr']);
                $dateOptions['attr'] = isset($dateOptions['attr']) ? array_merge($dateOptions['attr'], $ov) : $ov;
            }

            $timeOptions = array_intersect_key($options, array_flip([
                'hours',
                'minutes',
                'seconds',
                'with_minutes',
                'with_seconds',
                'empty_value',
                'placeholder',
                'choice_translation_domain',
                'required',
                'translation_domain',
                'html5',
                'invalid_message',
                'invalid_message_parameters',
                'time_attr', // <= attr for time widget
            ]));
            if (isset($timeOptions['time_attr'])) {
                $ov = $timeOptions['time_attr'];
                unset($timeOptions['time_attr']);
                $timeOptions['attr'] = isset($timeOptions['attr']) ? array_merge($timeOptions['attr'], $ov) : $ov;
            }

            if (null !== $options['date_widget']) {
                $dateOptions['widget'] = $options['date_widget'];
            }

            if (null !== $options['time_widget']) {
                $timeOptions['widget'] = $options['time_widget'];
            }

            if (null !== $options['date_format']) {
                $dateOptions['format'] = $options['date_format'];
            }

            $dateOptions['input']          = $timeOptions['input']          = 'array';
            $dateOptions['error_bubbling'] = $timeOptions['error_bubbling'] = true;

            $builder
                ->addViewTransformer(new DataTransformerChain([
                    new DateTimeToArrayTransformer($options['model_timezone'], $options['view_timezone'], $parts),
                    new ArrayToPartsTransformer([
                        'date' => $dateParts,
                        'time' => $timeParts,
                    ]),
                ]))
                ->add('date', 'Symfony\Component\Form\Extension\Core\Type\DateType', $dateOptions)
                ->add('time', 'Symfony\Component\Form\Extension\Core\Type\TimeType', $timeOptions)
            ;
        }

        if ('string' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToStringTransformer($options['model_timezone'], $options['model_timezone'])
            ));
        } elseif ('timestamp' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToTimestampTransformer($options['model_timezone'], $options['model_timezone'])
            ));
        } elseif ('array' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToArrayTransformer($options['model_timezone'], $options['model_timezone'], $parts)
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefined(array_merge($resolver->getDefinedOptions(), ['date_attr', 'time_attr']));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'date_time';
    }
}
