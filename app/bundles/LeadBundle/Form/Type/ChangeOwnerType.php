<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
final class ChangeOwnerType extends AbstractType
{
    public function __construct(
        private readonly UserModel $userModel,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // An imported campaign has no owner, as users cannot be identified on another instance.
        // Without a placeholder the first user would look selected.
        $ownerMissing = is_array($options['data'] ?? null)
            && array_key_exists('owner', $options['data'])
            && empty($options['data']['owner']);

        $builder->add(
            'owner',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.batch.add_to',
                'multiple'          => false,
                'choices'           => $this->userModel->getOwnerListChoices(),
                'required'          => true,
                'label_attr'        => ['class' => 'form-label'],
                'attr'              => ['class' => 'form-control'],
            ] + ($ownerMissing ? [
                'placeholder' => 'mautic.core.form.chooseone',
                'constraints' => [new NotBlank(message: 'mautic.core.value.required')],
            ] : [])
        );
    }
}
