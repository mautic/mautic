<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        $default = (int) $options['data']['batchlimit'] ?? $this->coreParametersHelper->get('mailer_memory_msg_limit');

        $builder->add(
            'batchlimit',
            TextType::class,
            [
                'label'       => false,
                'attr'        => ['class' => 'form-control'],
                'data'        => $default,
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
