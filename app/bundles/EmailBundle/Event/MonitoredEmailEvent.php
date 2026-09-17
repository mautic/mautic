<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class MonitoredEmailEvent extends Event
{
    private array $folders = [];

    public function __construct(
        private readonly FormBuilderInterface $formBuilder,
        private readonly array $data,
    ) {
    }

    /**
     * Get the FormBuilder for monitored_mailboxes FormType.
     */
    public function getFormBuilder(): FormBuilderInterface
    {
        return $this->formBuilder;
    }

    /**
     * Insert a folder to configure.
     */
    public function addFolder(string $bundleKey, string $folderKey, string $label, string $default = ''): void
    {
        $keyName = ($folderKey) ? $bundleKey.'_'.$folderKey : $bundleKey;

        $this->folders[$keyName] = [
            'label'   => $label,
            'default' => $default,
        ];
    }

    /**
     * Get the value set for a specific bundle/folder.
     *
     * @return string
     */
    public function getData($bundleKey, $folderKey, string $default = '')
    {
        $keyName = $bundleKey.'_'.$folderKey;

        return $this->data[$keyName] ?? $default;
    }

    /**
     * Get array of folders.
     */
    public function getFolders(): array
    {
        return $this->folders;
    }
}
