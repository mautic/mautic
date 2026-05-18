<?php

namespace Mautic\CoreBundle\Entity;

/**
 * @deprecated use CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE to change template params instead
 */
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
