<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class BatchSendType extends AbstractType
{
    private CoreParametersHelper $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $default = $options['data']['batchlimit'] ?? $this->coreParametersHelper->get('mailer_memory_msg_limit');

        $builder->add(
            'batchlimit',
            TextType::class,
            [
                'label'       => false,
                'attr'        => ['class' => 'form-control'],
                'data'        => (int) $default,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'batch_send';
    }
}
