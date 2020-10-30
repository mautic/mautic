<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Validator;

use Mautic\CoreBundle\Exception\InvalidValueException;
use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

class CustomFieldValidator
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    /**
     * @throws RecordNotFoundException
     * @throws InvalidValueException
     */
    public function validateFieldType(string $alias, string $fieldType): void
    {
        /** @var LeadField|null */
        $field = $this->fieldModel->getEntityByAlias($alias);

        if (!$field) {
            throw new RecordNotFoundException("Contact field with alias '{$alias}' was not found.");
        }

        if ($field->getType() !== $fieldType) {
            throw new InvalidValueException("Contact field '{$field->getAlias()}' is type of '{$field->getType()}' but must be type of '{$fieldType}'");
        }
    }
}
