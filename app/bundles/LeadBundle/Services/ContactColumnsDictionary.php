<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactColumnsDictionary
{
    protected \Mautic\LeadBundle\Model\FieldModel $fieldModel;

    private \Symfony\Contracts\Translation\TranslatorInterface $translator;

    private \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper;

    private array $fieldsList = [];
    private FieldList $fieldList;

    public function __construct(FieldModel $fieldModel, FieldList $fieldList, TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper)
    {
        $this->fieldModel           = $fieldModel;
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->fieldList = $fieldList;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        $columns = array_flip($this->coreParametersHelper->get('contact_columns', []));
        $fields  = $this->getFields();
        foreach ($columns as $alias=>&$column) {
            if (isset($fields[$alias])) {
                $column = $fields[$alias];
            }
        }

        return $columns;
    }

    public function getFields(): array
    {
        if ($this->fieldsList === []) {
            $this->fieldsList['name']        = sprintf(
                '%s %s',
                $this->translator->trans('mautic.core.firstname'),
                $this->translator->trans('mautic.core.lastname')
            );
            $this->fieldsList['email']       = $this->translator->trans('mautic.core.type.email');
            $this->fieldsList['location']    = $this->translator->trans('mautic.lead.lead.thead.location');
            $this->fieldsList['stage']       = $this->translator->trans('mautic.lead.stage.label');
            $this->fieldsList['points']      = $this->translator->trans('mautic.lead.points');
            $this->fieldsList['last_active'] = $this->translator->trans('mautic.lead.lastactive');
            $this->fieldsList['id']          = $this->translator->trans('mautic.core.id');
            $this->fieldsList                = $this->fieldsList + $this->fieldList->getFieldList(false);
        }

        return $this->fieldsList;
    }
}
