<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

class FieldAliasHelper
{
    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    /**
     * Cleans the alias and if it's not unique it will make it unique.
     */
    public function makeAliasUnique(LeadField $field): LeadField
    {
        // alias cannot be changed for existing fields
        if ($field->getId()) {
            return $field;
        }

        // set alias as name if alias is empty
        $alias = ($field->getAlias() ?: $field->getName()) ?: '';

        // clean the alias
        $alias = $this->fieldModel->cleanAlias($alias, 'f_', 25);

        // make sure alias is not already taken
        $repo      = $this->fieldModel->getRepository();
        $testAlias = $alias;
        $aliases   = $repo->getAliases($field->getId(), false, true, null);
        $count     = (int) in_array($testAlias, $aliases);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $count     = (int) in_array($testAlias, $aliases);
            ++$aliasTag;
        }

        if ($testAlias !== $alias) {
            $alias = $testAlias;
        }

        $field->setAlias($alias);

        return $field;
    }
}
