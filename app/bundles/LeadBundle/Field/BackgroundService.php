<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\LeadBundle\Field\Exception\ColumnAlreadyCreatedException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Mautic\LeadBundle\Model\FieldModel;

class BackgroundService
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var CustomFieldColumn
     */
    private $customFieldColumn;

    /**
     * @var LeadFieldSaver
     */
    private $leadFieldSaver;

    public function __construct(
        FieldModel $fieldModel,
        CustomFieldColumn $customFieldColumn,
        LeadFieldSaver $leadFieldSaver
    ) {
        $this->fieldModel        = $fieldModel;
        $this->customFieldColumn = $customFieldColumn;
        $this->leadFieldSaver    = $leadFieldSaver;
    }

    /**
     * @param int $leadFieldId
     *
     * @throws ColumnAlreadyCreatedException
     * @throws DBALException
     * @throws DriverException
     * @throws Exception\CustomFieldLimitException
     * @throws LeadFieldWasNotFoundException
     * @throws SchemaException
     * @throws \Mautic\CoreBundle\Exception\SchemaException
     */
    public function addColumn($leadFieldId)
    {
        $leadField = $this->fieldModel->getEntity($leadFieldId);
        if (null === $leadField) {
            throw new LeadFieldWasNotFoundException('LeadField entity was not found');
        }

        if (!$leadField->getColumnIsNotCreated()) {
            throw new ColumnAlreadyCreatedException('Column was already created');
        }

        $this->customFieldColumn->processCreateLeadColumn($leadField, false);

        $leadField->setColumnWasCreated();
        $this->leadFieldSaver->saveLeadFieldEntity($leadField, false);
    }
}
