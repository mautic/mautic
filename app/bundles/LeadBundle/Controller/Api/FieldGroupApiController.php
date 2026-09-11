<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Model\FieldGroupModel;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends CommonApiController<FieldGroup>
 */
final class FieldGroupApiController extends CommonApiController
{
    /**
     * @var class-string<FieldGroup>
     */
    protected $entityClass = FieldGroup::class;

    protected $entityNameOne = 'fieldGroup';

    protected $entityNameMulti = 'fieldGroups';

    /**
     * @var array<int, string>
     */
    protected $serializerGroups = ['fieldGroupDetails', 'fieldGroupList'];

    #[Required]
    public function autowireFieldGroupApiController(
        FieldGroupModel $fieldGroupModel,
    ): void {
        $this->model          = $fieldGroupModel;
        $this->permissionBase = $fieldGroupModel->getPermissionBase();
    }

    /**
     * The "Group order" dropdown is GUI-only; the API sets the order property directly.
     *
     * @return array<string, mixed>
     */
    protected function getEntityFormOptions(): array
    {
        return ['include_order_field' => false];
    }
}
