<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormNotesInterface
{
    /**
     * Returns the notes for the section of the integration form (if applicable).
     *
     * To return the notes for all the section,
     *
     * @code
     *      [
     *          'authorization' => ['note' => '<note>', 'type' => '<type>'],
     *          'features'      => ['note' => '<note>', 'type' => '<type>'],
     *          'field_mapping' => ['note' => '<note>', 'type' => '<type>'],
     *      ]
     * @endcode
     * The <note> can we replace with string, sentence or translation key.
     * The <type> can we one of from 'info', 'alert'.
     *
     * @return string[][]
     */
    public function getFormNotes(): array;
}
