<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class AddToCompanyActionType extends AbstractType
{
    public function __construct(
        protected RouterInterface $router
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'company',
            CompanyListType::class,
            [
                'multiple'    => false,
                'required'    => true,
                'modal_route' => false,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.company.choosecompany.notblank']
                    ),
                ],
            ]
        );

        $windowUrl = $this->router->generate(
            'mautic_company_action',
            [
                'objectAction' => 'new',
                'contentOnly'  => 1,
                'updateSelect' => 'campaignevent_properties_company',
            ]
        );

        $builder->add(
            'newCompanyButton',
            ButtonType::class,
            [
                'attr' => [
                    'class'   => 'btn btn-primary btn-nospin',
                    'onclick' => 'Mautic.loadNewWindow({"windowUrl": "'.$windowUrl.'"})',
                    'icon'    => 'fa fa-plus',
                ],
                'label' => 'mautic.company.new.company',
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'addtocompany_action';
    }
}
