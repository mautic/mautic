<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormBuilder;

class MonitoredEmailEvent extends Event
{
    /**
     * @var FormBuilder
     */
    private $formBuilder;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $folders = [];

    /**
     * @param FormBuilder $builder
     * @param array       $data
     */
    public function __construct(FormBuilder $builder, array $data)
    {
        $this->formBuilder = $builder;
        $this->data        = $data;
    }

    /**
     * Get the FormBuilder for monitored_mailboxes FormType.
     *
     * @return FormBuilder
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * Insert a folder to configure.
     *
     * @param        $bundleKey
     * @param        $folderKey
     * @param        $label
     * @param string $default
     */
    public function addFolder($bundleKey, $folderKey, $label, $default = '')
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
     * @param $bundleKey
     * @param $folderKey
     * @param $default
     *
     * @return string
     */
    public function getData($bundleKey, $folderKey, $default = '')
    {
        $keyName = $bundleKey.'_'.$folderKey;

        return (isset($this->data[$keyName])) ? $this->data[$keyName] : $default;
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
