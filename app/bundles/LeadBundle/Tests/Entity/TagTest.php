<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\Tag;

class TagTest extends \PHPUnit\Framework\TestCase
{
    public function testSetTagByConstructor()
    {
        $entity = new Tag('tagA');

        $this->assertSame('tagA', $entity->getTag());
    }

    public function testSetTagBySetter()
    {
        $entity = new Tag();
        $entity->setTag('tagA');

        $this->assertSame('tagA', $entity->getTag());
    }

    public function testTagValidation()
    {
        $sampleTags = [
            'hello world'                                         => 'hello world',
            'hello&#34; world'                                    => 'hello" world',
            'trim whitespace'                                     => ' trim whitespace ',
            'trim tab'                                            => "\ttrim tab\t",
            '&#60;script&#62;console.log(hello)&#60;/script&#62;' => '<script>console.log(hello)</script>',
            'oěř§ůú.'                                             => 'oěř§ůú.',
        ];

        foreach ($sampleTags as $expected => $tag) {
            $entity = new Tag($tag);
            $this->assertSame($expected, $entity->getTag());
        }
    }

    public function testDisabledValidation()
    {
        $sampleTags = [
            'hello world'      => 'hello world',
            'hello&#34; world' => 'hello&#34; world',
            'oěř§ůú.'          => 'oěř§ůú.',
        ];

        foreach ($sampleTags as $expected => $tag) {
            $entity = new Tag($tag, false);
            $this->assertSame($expected, $entity->getTag());
        }
    }
}
