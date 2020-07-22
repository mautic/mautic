<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Form\Type;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait NotBlankIfPublishedConstraintTrait
{
    /**
     * Get not blank restraint if published.
     *
     * @return callback
     */
    private function getNotBlankConstraint()
    {
        return new Callback(
            function ($validateMe, ExecutionContextInterface $context): void {
                /** @var Integration $data */
                $data = $context->getRoot()->getData();
                if (!empty($data->getIsPublished()) && empty($validateMe)) {
                    $context->buildViolation('mautic.core.value.required')->addViolation();
                }
            }
        );
    }
}
