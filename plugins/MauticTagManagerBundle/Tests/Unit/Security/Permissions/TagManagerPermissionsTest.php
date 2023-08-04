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
    private $tagManagerPermissions;

    /**
     * @var FormBuilderInterface
     */
    private $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagManagerPermissions = $this->getMockBuilder(TagManagerPermissions::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'addStandardFormFields',
            ])
            ->getMock();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
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
