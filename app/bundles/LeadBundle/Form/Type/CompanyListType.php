<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CompanyListType extends AbstractType
{
    public const DEFAULT_LIMIT = 100;

    public function __construct(
        private CompanyRepository $companyRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'label'               => 'mautic.lead.lead.companies',
                'entity_label_column' => 'companyname',
                'modal_route'         => 'mautic_company_action',
                'modal_header'        => 'mautic.company.new.company',
                'model'               => 'lead.company',
                'ajax_lookup_action'  => 'lead:getLookupChoiceList',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => fn (Options $options): array => [
                    'type'      => 'lead.company',
                    'limit'     => self::DEFAULT_LIMIT,
                ] + ((isset($options['model_lookup_method']) && ('getSimpleLookupResults' === $options['model_lookup_method'])) ? ['exclude' => $options['main_entity']] : []),
                'multiple'            => true,
                'main_entity'         => null,
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $form->getData();
        if ($data) {
            $selectedIds     = is_array($data) ? $data : [$data];
            $existingChoices = array_column($view->vars['choices'], 'value');
            $missingIds      = array_diff($selectedIds, $existingChoices);

            if ($missingIds) {
                $missingCompanies = $this->companyRepository->findBy(['id' => $missingIds]);
                foreach ($missingCompanies as $company) {
                    $view->vars['choices'][] = new ChoiceView(
                        $company->getId(),
                        (string) $company->getId(),
                        $company->getName()
                    );
                }
            }
        }
    }

    public function getParent(): ?string
    {
        return EntityLookupType::class;
    }
}
