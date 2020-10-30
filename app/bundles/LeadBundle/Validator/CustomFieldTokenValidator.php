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

use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\DataObject\ContactFieldToken;
use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;

class CustomFieldTokenValidator
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
    public function validateFieldType(ContactFieldToken $contactFieldToken, string $fieldType): void
    {
        /** @var Field|null */
        $field = $this->fieldModel->getEntityByAlias($contactFieldToken->getFieldAlias());

        if (!$field) {
            throw new RecordNotFoundException("Custom field with alias {$contactFieldToken->getFieldAlias()} was not found.");
        }

        if ($field->getType() !== $fieldType) {
            throw new InvalidValueException("Field {$field->getAlias()} is type of {$field->getType()} but must be type of {$fieldType}");
        }
    }
}
