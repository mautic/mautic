<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Unit\Security\Permissions;

use MauticPlugin\MauticTagManagerBundle\Security\Permissions\TagManagerPermissions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

class TagManagerPermissionsTest extends TestCase
{
    /**
     * @var TagManagerPermissions
     */
    private \PHPUnit\Framework\MockObject\MockObject $tagManagerPermissions;

    /**
     * @var FormBuilderInterface&\PHPUnit\Framework\MockObject\Stub
     */
    private \PHPUnit\Framework\MockObject\Stub $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagManagerPermissions = $this->getMockBuilder(TagManagerPermissions::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'addStandardFormFields',
            ])
            ->getMock();

        $this->formBuilder = $this->createStub(FormBuilderInterface::class);
    }

    public function testBuildFormMethodAddsStandardFormFields(): void
    {
        $options = ['someOption'];
        $data    = ['someData'];
        $this->tagManagerPermissions->expects($this->once())
            ->method('addStandardFormFields')
            ->with('tagManager', 'tagManager', $this->formBuilder, $data);

        $this->tagManagerPermissions->buildForm($this->formBuilder, $options, $data);
    }
}
