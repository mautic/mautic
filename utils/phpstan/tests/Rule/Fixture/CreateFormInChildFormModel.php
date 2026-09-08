<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\Form\FormInterface;

/**
 * @extends FormModel<object>
 */
final class CreateFormInChildFormModel extends FormModel
{
    /**
     * @param mixed[] $options
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        return $this->formFactory->create($entity::class, $entity, $options);
    }
}
