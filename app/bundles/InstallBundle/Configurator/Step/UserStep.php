<?php

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\UserStepType;

class UserStep implements StepInterface
{
    /**
     * User's first name.
     */
    public $firstname;

    /**
     * User's last name.
     */
    public $lastname;

    /**
     * User's e-mail address.
     */
    public $email;

    /**
     * User's username.
     */
    public $username;

    /**
     * User's password.
     */
    public $password;

    /**
     * {@inheritdoc}
     */
    public function getFormType(): string
    {
        return UserStepType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '@MauticInstall/Install/user.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data): array
    {
        return [];
    }
}
