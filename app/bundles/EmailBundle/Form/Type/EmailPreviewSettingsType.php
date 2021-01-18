<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailPreviewSettingsType extends AbstractType
{
    /**
     * @var EmailRepository
     */
    private $emailRepository;

    public function __construct(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Email $email */
        $email = $options['email'];

        $builder->add(
            'translation',
            ChoiceType::class,
            [
                'choices' => [],
            ]
        );

        $variants = $this->emailRepository->fetchPublishedEmailsWithVariantById($email->getId());

        $builder->add(
            'variant',
            ChoiceType::class,
            [
                'choices' => [],
            ]
        );

        $builder->add(
            'contact',
            LookupType::class,
            []
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['email' => null]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'email_preview_settings';
    }
}
