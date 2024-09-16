<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Translation\TranslatorInterface;

class ContactColumnsDictionary
{
    protected $fieldModel;

    private $translator;

    private $coreParametersHelper;

    private $fieldList = [];

    public function __construct(FieldModel $fieldModel, TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper)
    {
        $this->fieldModel           = $fieldModel;
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
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

    /**
     * @return array
     */
    public function getFields()
    {
        if (empty($this->fieldList)) {
            $this->fieldList['name']        = sprintf(
                '%s %s',
                $this->translator->trans('mautic.core.firstname'),
                $this->translator->trans('mautic.core.lastname')
            );
            $this->fieldList['email']       = $this->translator->trans('mautic.core.type.email');
            $this->fieldList['location']    = $this->translator->trans('mautic.lead.lead.thead.location');
            $this->fieldList['stage']       = $this->translator->trans('mautic.lead.stage.label');
            $this->fieldList['points']      = $this->translator->trans('mautic.lead.points');
            $this->fieldList['last_active'] = $this->translator->trans('mautic.lead.lastactive');
            $this->fieldList['id']          = $this->translator->trans('mautic.core.id');
            $this->fieldList                = $this->fieldList + $this->fieldModel->getFieldList(false);
        }

        return $this->fieldList;
    }
}
