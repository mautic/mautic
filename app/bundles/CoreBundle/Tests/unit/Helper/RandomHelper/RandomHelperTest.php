<?php

namespace Mautic\CoreBundle\Helper\RandomHelper;

/**
 * Class RandomHelperTest.
 */
class RandomHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Based on https://github.com/nette/utils/blob/master/tests/Utils/Random.generate().phpt.
     */
    public function testGenerate()
    {
        $randomHelper = $this->getRandomHelper();
        $this->assertSame(10, strlen($randomHelper->generate(10)));
        $this->assertSame(5, strlen($randomHelper->generate(5)));
        $this->assertSame(200, strlen($randomHelper->generate(200)));
        $this->assertTrue((bool) preg_match('#^[0-9a-z]+$#', $randomHelper->generate()));
        $this->assertTrue((bool) preg_match('#^[0-9]+$#', $randomHelper->generate(1000, '0-9')));
        $this->assertTrue((bool) preg_match('#^[0a-z12]+$#', $randomHelper->generate(1000, '0a-z12')));
        $this->assertTrue((bool) preg_match('#^[-a]+$#', $randomHelper->generate(1000, '-a')));

        $this->expectException(\InvalidArgumentException::class);
        $randomHelper->generate();

        $this->expectException(\InvalidArgumentException::class);
        $randomHelper->generate(1, '000');
    }

    /**
     * @return RandomHelper
     */
    private function getRandomHelper()
    {
        return new RandomHelper();
    }
}
