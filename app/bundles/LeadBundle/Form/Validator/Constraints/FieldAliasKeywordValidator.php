<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Helper\FieldAliasHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class FieldAliasKeywordValidator extends ConstraintValidator
{
    public const RESTRICTED_ALIASES = [
        'contact_id',
        'company_id',
    ];

    public function __construct(
        private ListModel $listModel,
        private FieldAliasHelper $aliasHelper,
        private EntityManager $em,
        private TranslatorInterface $translator,
        private ContactSegmentFilterDictionary $contactSegmentFilterDictionary,
    ) {
    }

    /**
     * @param LeadField $field
     */
    public function validate($field, Constraint $constraint): void
    {
        $oldValue = $this->em->getUnitOfWork()->getOriginalEntityData($field);
        $this->aliasHelper->makeAliasUnique($field);

        // If empty it's a new object else it's an edit
        if (empty($oldValue) || (!empty($oldValue) && is_array($oldValue) && $oldValue['alias'] != $field->getAlias())) {
            if (in_array($field->getAlias(), self::RESTRICTED_ALIASES)) {
                $this->context->addViolation(
                    $this->translator->trans(
                        'mautic.lead.field.keyword.restricted',
                        ['%alias%' => $field->getAlias()],
                        'validators'
                    )
                );

                return;
            }
            $choices = array_merge($this->listModel->getChoiceFields()[$field->getObject()] ?? [], $this->contactSegmentFilterDictionary->getFilters());

            if (isset($choices[$field->getAlias()])) {
                $this->context->addViolation($constraint->message, ['%keyword%' => $field->getAlias()]);
            }
        }
    }
}
