<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class CompanySegmentsPermissions extends AbstractPermissions
{
    /**
     * @param array<mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions(['companysegments']);
    }

    public function getName(): string
    {
        return 'companysegment';
    }

    /**
     * @param array<mixed> $options
     * @param array<mixed> $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addExtendedFormFields('companysegment', 'companysegments', $builder, $data);
    }
}
