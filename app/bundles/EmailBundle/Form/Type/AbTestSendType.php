<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template TData of Email
 */
class AbTestSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
