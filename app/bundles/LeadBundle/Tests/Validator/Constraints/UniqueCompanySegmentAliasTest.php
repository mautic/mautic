<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\UniqueCompanySegmentAlias;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class UniqueCompanySegmentAliasTest extends TestCase
{
    public function testThrowsConstraintExceptionIfNoFieldIsSet(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The options "field" must be set for constraint');
        new UniqueCompanySegmentAlias();
    }
}
