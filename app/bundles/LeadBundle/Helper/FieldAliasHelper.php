<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

class FieldAliasHelper
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @param FieldModel $fieldModel
     */
    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    /**
     * Cleans the alias and if it's not unique it will make it unique.
     *
     * @param LeadField $field
     *
     * @return LeadField
     */
    public function makeAliasUnique(LeadField $field)
    {
        // alias cannot be changed for existing fields
        if ($field->getId()) {
            return $field;
        }

        // set alias as name if alias is empty
        $alias = $field->getAlias() ?: $field->getName();

        // clean the alias
        $alias = $this->fieldModel->cleanAlias($alias, 'f_', 25);

        // make sure alias is not already taken
        $repo      = $this->fieldModel->getRepository();
        $testAlias = $alias;
        $aliases   = $repo->getAliases($field->getId(), false, true, $field->getObject());
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
