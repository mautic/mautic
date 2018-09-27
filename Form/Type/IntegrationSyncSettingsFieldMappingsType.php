<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;


use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    use FilteredFieldsTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * IntegrationSyncSettingsFieldMappingsType constructor.
     *
     * @param TranslatorInterface $translator
     * @param FieldModel          $fieldModel
     * @param RequestStack        $requestStack
     */
    public function __construct(TranslatorInterface $translator, FieldModel $fieldModel, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->fieldModel = $fieldModel;
        $this->request    = $requestStack->getCurrentRequest();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidFormOptionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!is_array($options['objects'])) {
            throw new InvalidFormOptionException("objects must be an array");
        }

        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException("integrationObject must be an instance of ConfigFormSyncInterface");
        }

        $page    = $this->request ? $this->request->get('page', 1) : 1;
        $keyword = $this->request ? $this->request->get('keyword', null) : null;

        foreach ($options['objects'] as $objectName => $objectLabel) {
            $this->filterFields($integrationObject, $objectName, $keyword, $page);

            $builder->add(
                $objectName,
                IntegrationSyncSettingsObjectFieldMappingType::class,
                [
                    'label'                     => false,
                    'requiredIntegrationFields' => $this->getRequiredFields(),
                    'integrationFields'         => $this->getFilteredFields(),
                    'mauticFields'              => $this->fieldModel->getFieldList(false),
                    'page'                      => $page ? $page : 1,
                    'keyword'                   => $keyword,
                    'totalFieldCount'           => $this->getTotalFieldCount(),
                    'object'                    => $objectName,
                    'integration'               => $integrationObject->getName()
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'integrationObject',
                'objects'
            ]
        );
    }

}