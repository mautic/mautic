<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\CoreBundle\Exception\RecordNotUnpublishedException;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator as InternalValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SegmentUsedInCampaignsValidator extends ConstraintValidator
{
    /**
     * @var InternalValidator
     */
    private $internalValidator;

    public function __construct(InternalValidator $internalValidator)
    {
        $this->internalValidator = $internalValidator;
    }

    public function validate($segment, Constraint $constraint): void
    {
        try {
            /** @var LeadList $segment */
            if ($segment->getIsPublished()) {
                return;
            }

            $this->internalValidator->validate($segment);
        } catch (RecordNotUnpublishedException $exception) {
            $this->context->buildViolation($exception->getMessage())
                ->atPath('isPublished')
                ->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->addViolation();
        }
    }
}
