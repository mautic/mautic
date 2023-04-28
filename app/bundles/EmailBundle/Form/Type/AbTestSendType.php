<?php

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AbTestSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
