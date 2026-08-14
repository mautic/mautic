<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

/**
 * @deprecated 2.3 - to be removed in 3.0; use AbstractFormController instead
 */
class FormController extends AbstractStandardFormController
{
    protected function getModelName(): string
    {
        return '';
    }

    /**
     * @return mixed[]
     */
    public function getViewArguments(array $args, $action): array
    {
        return $args;
    }

    protected function getJsLoadMethodPrefix(): ?string
    {
        return null;
    }

    protected function getRouteBase(): ?string
    {
        return null;
    }

    /**
     * @return mixed
     */
    protected function getSessionBase($objectId = null)
    {
        return parent::getSessionBase($objectId);
    }

    protected function getTemplateBase(): ?string
    {
        return null;
    }

    protected function getTranslationBase(): ?string
    {
        return null;
    }

    protected function getPermissionBase(): ?string
    {
        return null;
    }
}
