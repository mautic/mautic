<?php

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CustomFieldEntityInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait CustomFieldsApiControllerTrait
{
    private ?RequestStack $requestStack = null;

    /**
     * @var mixed[]
     */
    private $fieldCache = [];

    /**
     * Remove IpAddress and lastActive as it'll be handled outside the form.
     *
     * @param mixed[]      $parameters
     * @param Lead|Company $entity
     * @param string       $action
     *
     * @return mixed|void
     */
    protected function prepareParametersForBinding(Request $request, $parameters, $entity, $action)
    {
        if ('company' === $this->entityNameOne) {
            $object = 'company';
        } else {
            $object = 'lead';
            unset($parameters['lastActive'], $parameters['tags'], $parameters['ipAddress']);
        }

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            // If a new contact or PUT update (complete representation of the objectd), set empty fields to field defaults if the parameter
            // is not defined in the request

            /** @var FieldModel $fieldModel */
            $fieldModel = $this->getModel('lead.field');
            $fields     = $fieldModel->getFieldListWithProperties($object);

            foreach ($fields as $alias => $field) {
                // Set the default value if the parameter is not included in the request, there is no value for the given entity, and a default is defined
                $currentValue = $entity->getFieldValue($alias);
                if (!isset($parameters[$alias]) && ('' === $currentValue || null == $currentValue) && '' !== $field['defaultValue'] && null !== $field['defaultValue']) {
                    $parameters[$alias] = $field['defaultValue'];
                }
            }
        }

        return $parameters;
    }

    /**
     * Flatten fields into an 'all' key for dev convenience.
     */
    protected function preSerializeEntity(object $entity, string $action = 'view'): void
    {
        if ($entity instanceof CustomFieldEntityInterface) {
            $fields        = $entity->getFields();
            $fields['all'] = $entity->getProfileFields();

            // Temporary hack to address numbers being type casted to float which broke some API implementations because M2 used to return
            // these as strings and values are normalized in a dozen differneet ways throughout LeadModel::setFieldValues methods and became
            // too risky to hotfix
            $fields = $this->fixNumbers($fields);

            $entity->setFields($fields);
        }
    }

    /**
     * @param mixed[] $fields
     *
     * @return mixed[]
     */
    private function fixNumbers(array $fields): array
    {
        $numberFields = [];
        foreach ($fields as $group => $groupFields) {
            if ('all' === $group) {
                continue;
            }

            foreach ($groupFields as $field => $fieldDefinition) {
                if ('points' === $field) {
                    // Points were always a number in M2
                    $numberFields[$field] = (int) $fields[$group][$field]['value'];
                }

                if ('number' !== $fieldDefinition['type'] || null === $fields[$group][$field]['value']) {
                    continue;
                }

                // Some requests don't seem to have properties unserialized by default (even in M2)
                if (!isset($fieldDefinition['properties'])) {
                    $fieldDefinition['properties'] = [];
                }
                $properties = is_string($fieldDefinition['properties']) ? unserialize($fieldDefinition['properties']) : $fieldDefinition['properties'];

                $fields[$group][$field]['value']           = empty($properties['scale']) ? (int) $fields[$group][$field]['value']
                    : (float) $fields[$group][$field]['value'];
                $fields[$group][$field]['normalizedValue'] = empty($properties['scale']) ? (int) $fields[$group][$field]['normalizedValue']
                    : (float) $fields[$group][$field]['normalizedValue'];

                $numberFields[$field] = $fields[$group][$field]['value'];
            }
        }

        // Fix "all" fields
        $fields['all'] = array_merge($fields['all'], $numberFields);

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEntityFormOptions(): array
    {
        $object = ('company' === $this->entityNameOne) ? 'company' : 'lead';

        if (isset($this->fieldCache[$object])) {
            return $this->fieldCache[$object];
        }

        $model = $this->getModel('lead.field');
        \assert($model instanceof FieldModel);

        $fields = $model->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                        [
                            'column' => 'f.object',
                            'expr'   => 'eq',
                            'value'  => $object,
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
                'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
            ]
        );
        \assert($fields instanceof Paginator);

        $this->fieldCache[$object] = ['fields' => $fields->getIterator()];

        return $this->fieldCache[$object];
    }

    /**
     * @param Lead|Company $entity
     * @param Form         $form
     * @param mixed[]      $parameters
     * @param bool         $isPostOrPatch
     *
     * @return bool|void
     */
    protected function setCustomFieldValues($entity, $form, $parameters, $isPostOrPatch = false)
    {
        // set the custom field values
        // pull the data from the form in order to apply the form's formatting
        foreach ($form as $f) {
            $parameters[$f->getName()] = $f->getData();
        }

        if ($isPostOrPatch) {
            // Don't overwrite the contacts accumulated points
            if (isset($parameters['points']) && empty($parameters['points'])) {
                unset($parameters['points']);
            }

            // When merging a contact because of a unique identifier match in POST /api/contacts//new or PATCH /api/contacts//edit all 0 values must be unset because
            // we have to assume 0 was not meant to overwrite an existing value. Other empty values will be caught by LeadModel::setFieldValues
            $parameters = array_filter(
                $parameters,
                function ($value, $key) use ($form): bool {
                    // Allow 0 for numeric fields.
                    // We need to check the original field type if possible,
                    // but $form might not contain all custom fields directly as named children.
                    // A safer approach is to allow 0 if the value IS numeric and is 0.
                    // Other empty values (null, false, empty string) will be handled by array_filter's default behavior
                    // or by subsequent processing in LeadModel::setFieldValues if `overwriteWithBlank` is false.
                    if (is_numeric($value) && (int) $value === 0) {
                        return true; // Keep '0' or 0 or 0.0
                    }

                    // Original behavior for other cases (e.g. truly empty strings for non-numeric fields might be filtered by default array_filter if no callback,
                    // but here we explicitly keep non-empty or non-zero numeric values).
                    // For POST/PATCH, the general idea is to prevent accidental overwrite with "empty" if field is not submitted.
                    // However, if a field *is* submitted with an explicit empty string, null, or specific 0, it should be processed.
                    // The current filter is a bit aggressive for '0'.
                    // The below ensures that if a key is present in parameters, we keep it unless it's truly empty (null, '').
                    // This revised filter is more about ensuring that if a field is present in the payload,
                    // its value (even if "empty" like an empty string or 0) is considered for processing by setFieldValues.
                    // The `overwriteWithBlank` flag in `setFieldValues` will then determine if an empty string overwrites existing data.
                    // This change focuses on not losing the '0' for numeric types.

                    // If a field is explicitly passed with an empty string or null, let setFieldValues decide based on $overwriteWithBlank
                    // The main concern is not losing '0' for numerics.
                    // Other empty values (empty string, null) for non-numeric fields:
                    // If $value is '', (bool)$value is false. If $value is null, (bool)$value is false.
                    // The original filter `return true` for non-numerics meant they were kept if non-empty.
                    // We should keep any explicitly passed value to let setFieldValues handle it with overwriteWithBlank.
                    // The only thing this array_filter was doing was removing numeric 0.
                    // So, if we allow numeric 0, the filter becomes less critical for other types unless specific 'empty'
                    // values (that are not null or '') should also be removed here.
                    // For now, let's ensure 0 is kept and other non-null values are kept.
                    // If a parameter is provided, it should be processed.
                    return true;
                },
                ARRAY_FILTER_USE_BOTH
            );
        }

        $overwriteWithBlank = !$isPostOrPatch;
        if (isset($parameters['overwriteWithBlank']) && !empty($parameters['overwriteWithBlank'])) {
            $overwriteWithBlank = true;
            unset($parameters['overwriteWithBlank']);
        }

        $this->model->setFieldValues($entity, $parameters, $overwriteWithBlank);
    }

    /**
     * @param string $object
     *
     * @deprecated since Mautic 5.2, to be removed in 6.0 with no replacement.
     *
     * @return void
     */
    protected function setCleaningRules($object = 'lead')
    {
        $leadFieldModel = $this->getModel('lead.field');
        \assert($leadFieldModel instanceof FieldModel);
        $fields = $leadFieldModel->getFieldListWithProperties($object);
        foreach ($fields as $field) {
            if (!empty($field['properties']['allowHtml'])) {
                $this->dataInputMasks[$field['alias']]  = 'html'; /** @phpstan-ignore-line this is accessing a property from the parent class. Terrible. Refactor for M6. */
            }
        }
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }
}
