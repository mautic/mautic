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

namespace MauticPlugin\MauticFocusBundle\Tests\Form\Type;

use MauticPlugin\MauticFocusBundle\Form\Type\ContentType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

class ContentTypeTest extends TestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|FormBuilderInterface
     */
    private $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
    }

    public function testBuilderForm(): void
    {
        $this->formBuilder->expects(self::exactly(7))->method('add')->willReturnSelf();
        $options     = [];
        $contentType = new ContentType();
        $contentType->buildForm($this->formBuilder, $options);
    }
}
