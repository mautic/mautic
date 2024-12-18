<?php

namespace Mautic\EmailBundle\Event;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MonitoredEmailEvent extends Event
{
    private array $folders = [];

    public function __construct(
        private FormBuilderInterface $formBuilder,
        private array $data,
    ) {
    }

    /**
     * Get the FormBuilder for monitored_mailboxes FormType.
     *
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * Insert a folder to configure.
     *
     * @param string $default
     */
    public function addFolder($bundleKey, $folderKey, $label, $default = ''): void
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
    public function getData($bundleKey, $folderKey, $default = '')
    {
        $keyName = $bundleKey.'_'.$folderKey;

        return $this->data[$keyName] ?? $default;
    }

    /**
     * Get array of folders.
     *
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }
}
