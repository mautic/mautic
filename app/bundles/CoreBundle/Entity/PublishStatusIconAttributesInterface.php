<?php

namespace Mautic\CoreBundle\Entity;

interface PublishStatusIconAttributesInterface
{
    public function getOnclickMethod(): string;

    /**
     * @return array<string, string>
     */
    public function getDataAttributes(): array;

    /**
     * @return array<string, string>
     */
    public function getTranslationKeysDataAttributes(): array;
}
