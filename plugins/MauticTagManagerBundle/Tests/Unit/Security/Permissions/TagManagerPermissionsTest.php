<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Security\Permissions;

use MauticPlugin\MauticTagManagerBundle\Security\Permissions\TagManagerPermissions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class TagManagerPermissionsTest extends TestCase
{
    /**
     * @var TagManagerPermissions
     */
    private \PHPUnit\Framework\MockObject\MockObject $tagManagerPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagManagerPermissions = $this->getMockBuilder(TagManagerPermissions::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'addStandardFormFields',
            ])
            ->getMock();
    }

    public function testBuildFormMethodAddsStandardFormFields(): void
    {
        $options = ['someOption'];
        $data    = ['someData'];
        $this->tagManagerPermissions->expects($this->once())
            ->method('addStandardFormFields')
            ->with('tagManager', 'tagManager', $this->createStub(FormBuilderInterface::class), $data);

        $this->tagManagerPermissions->buildForm($this->createStub(FormBuilderInterface::class), $options, $data);
    }
}
