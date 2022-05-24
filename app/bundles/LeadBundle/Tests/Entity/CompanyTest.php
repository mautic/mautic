<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\Company;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Tests\Entity\UserFake;
use PHPUnit\Framework\Assert;

class CompanyTest extends \PHPUnit\Framework\TestCase
{
    public function testChangingPropertiesHydratesFieldChanges(): void
    {
        $email    = 'foo@bar.com';
        $company  = new Company();
        $company->addUpdatedField('email', $email);
        $changes = $company->getChanges();

        $this->assertFalse(empty($changes['fields']['email']));

        $this->assertEquals($email, $changes['fields']['email'][1]);
    }

    public function testNameChange(): void
    {
        $company = new Company();
        $company->setName('Evil Corp');
        Assert::assertEquals(['name' => [null, 'Evil Corp']], $company->getChanges());

        $company->setName('Peace Corp');
        Assert::assertEquals(['name' => ['Evil Corp', 'Peace Corp']], $company->getChanges());

        $company->setName(null);
        Assert::assertEquals(['name' => ['Peace Corp', null]], $company->getChanges());
    }

    /**
     * @dataProvider ownerProvider
     * 
     * @param mixed[] $expectedChanges
     */
    public function testOwnerChange(?User $currentOwner, ?User $newOnwer, array $expectedChanges): void
    {
        $company = new Company();
        $company->setOwner($currentOwner);
        $company->setOwner($newOnwer);
        Assert::assertEquals($expectedChanges, $company->getChanges());
    }

    /**
     * @return iterable<mixed[]>
     */
    public function ownerProvider(): iterable
    {
        yield [null, null, []];
        yield [new UserFake(11), null, ['owner' => [11, null]]];
        yield [new UserFake(11), new UserFake(345), ['owner' => [11, 345]]];
        yield [new UserFake(11), new UserFake(11), []];
        yield [null,new UserFake(11), ['owner' => [null, 11]]];
    }
}
