<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CustomFieldEntityInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\Form;

trait CustomFieldsApiControllerTrait
{
    /**
     * Remove IpAddress and lastActive as it'll be handled outside the form.
     *
     * @param $parameters
     * @param Lead $entity
     * @param $action
     *
     * @return mixed|void
     */
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        if ('company' === $this->entityNameOne) {
            $object = 'company';
        } else {
            $object = 'lead';
            unset($parameters['lastActive'], $parameters['tags'], $parameters['ipAddress']);
        }

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
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
     *
     * @param        $entity
     * @param string $action
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        if ($entity instanceof CustomFieldEntityInterface) {
            $fields        = $entity->getFields();
            $fields['all'] = $entity->getProfileFields();
            $entity->setFields($fields);
        }
    }

    /**
     * @return array
     */
    protected function getEntityFormOptions()
    {
        $object = ('company' === $this->entityNameOne) ? 'company' : 'lead';
        $fields = $this->getModel('lead.field')->getEntities(
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
            ]
        );

        return ['fields' => $fields];
    }

    /**
     * @param Lead|Company $entity
     * @param Form         $form
     * @param array        $parameters
     * @param bool         $isPost
     */
    protected function setCustomFieldValues($entity, $form, $parameters, $isPost = false)
    {
        //set the custom field values
        //pull the data from the form in order to apply the form's formatting
        foreach ($form as $f) {
            $parameters[$f->getName()] = $f->getData();
        }

        if ($isPost) {
            // Don't overwrite the contacts accumulated points
            if (isset($parameters['points']) && empty($parameters['points'])) {
                unset($parameters['points']);
            }

            // When merging a contact because of a unique identifier match in POST /api/contacts//new, all 0 values must be unset because
            // we have to assume 0 was not meant to overwrite an existing value. Other empty values will be caught by LeadModel::setCustomFieldValues
            $parameters = array_filter(
                $parameters,
                function ($value) {
                    if (is_numeric($value)) {
                        return 0 !== (int) $value;
                    }

                    return true;
                }
            );
        }

        $this->model->setFieldValues($entity, $parameters, !$isPost);
    }
}
