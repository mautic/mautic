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

use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Symfony\Component\Translation\TranslatorInterface;

class FieldList
{
    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LeadFieldRepository $leadFieldRepository, TranslatorInterface $translator)
    {
        $this->leadFieldRepository = $leadFieldRepository;
        $this->translator          = $translator;
    }

    /**
     * @param bool|true $byGroup
     * @param bool|true $alphabetical
     * @param array     $filters
     *
     * @return array
     */
    public function getFieldList($byGroup = true, $alphabetical = true, $filters = ['isPublished' => true, 'object' => 'lead'])
    {
        $forceFilters = [];
        foreach ($filters as $col => $val) {
            $forceFilters[] = [
                'column' => "f.{$col}",
                'expr'   => 'eq',
                'value'  => $val,
            ];
        }
        // Get a list of custom form fields
        $fields = $this->leadFieldRepository->getEntities([
             'filter' => [
                 'force' => $forceFilters,
             ],
             'orderBy'    => 'f.order',
             'orderByDir' => 'asc',
        ]);

        $leadFields = [];

        foreach ($fields as $f) {
            if ($byGroup) {
                $fieldName                              = $this->translator->trans('mautic.lead.field.group.'.$f->getGroup());
                $leadFields[$fieldName][$f->getAlias()] = $f->getLabel();
            } else {
                $leadFields[$f->getAlias()] = $f->getLabel();
            }
        }

        if ($alphabetical) {
            // Sort the groups
            uksort($leadFields, 'strnatcmp');

            if ($byGroup) {
                // Sort each group by translation
                foreach ($leadFields as &$fieldGroup) {
                    uasort($fieldGroup, 'strnatcmp');
                }
            }
        }

        return $leadFields;
    }
}
