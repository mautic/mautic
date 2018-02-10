<?php

/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see         http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\FormBundle\Validator\Constraint\FileExtensionConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * Class FormFieldFileType.
 */
class FormFieldFileType extends AbstractType
{
    const PROPERTY_ALLOWED_FILE_EXTENSIONS = 'allowed_file_extensions';
    const PROPERTY_ALLOWED_FILE_SIZE       = 'allowed_file_size';

    /** @var CoreParametersHelper */
    private $coreParametersHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CoreParametersHelper $coreParametersHelper, TranslatorInterface $translator)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->translator           = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['data'][self::PROPERTY_ALLOWED_FILE_EXTENSIONS])) {
            $options['data'][self::PROPERTY_ALLOWED_FILE_EXTENSIONS] = $this->coreParametersHelper->getParameter('allowed_extensions');
        }
        if (empty($options['data'][self::PROPERTY_ALLOWED_FILE_SIZE])) {
            $options['data'][self::PROPERTY_ALLOWED_FILE_SIZE] = $this->coreParametersHelper->getParameter('max_size');
        }

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create(
                self::PROPERTY_ALLOWED_FILE_EXTENSIONS,
                TextareaType::class,
                [
                    'label'      => 'mautic.form.field.file.allowed_extensions',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.file.tooltip.allowed_extensions',
                    ],
                    'data'        => $options['data'][self::PROPERTY_ALLOWED_FILE_EXTENSIONS],
                    'constraints' => [new FileExtensionConstraint()],
                ]
            )->addViewTransformer($arrayStringTransformer)
        );

        $maxUploadSize = FileHelper::getMaxUploadSizeInMegabytes();
        $builder->add(
            self::PROPERTY_ALLOWED_FILE_SIZE,
            TextType::class,
            [
                'label'      => 'mautic.form.field.file.allowed_size',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => $this->translator->trans('mautic.form.field.file.tooltip.allowed_size', ['%uploadSize%' => $maxUploadSize]),
                ],
                'data'        => $options['data'][self::PROPERTY_ALLOWED_FILE_SIZE],
                'constraints' => [new LessThanOrEqual(['value' => $maxUploadSize])],
            ]
        );
    }
}
