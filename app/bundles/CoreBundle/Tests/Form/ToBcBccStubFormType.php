<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Form;

use Mautic\CoreBundle\Form\ToBcBccFieldsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
final class ToBcBccStubFormType extends AbstractType
{
    use ToBcBccFieldsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addToBcBccFields($builder);
    }
}
