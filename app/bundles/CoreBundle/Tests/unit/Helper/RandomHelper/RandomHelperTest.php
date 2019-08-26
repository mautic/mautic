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
        $randomHelper->generate(0);
        $this->expectException(\InvalidArgumentException::class);
        $randomHelper->generate(1, '000');

        // frequency check
        $length = (int) 1e6;
        $delta  = 0.1;
        $s      = $randomHelper->generate($length, "\x01-\xFF");
        $freq   = count_chars($s);
        $this->assertSame(0, $freq[0]);
        for ($i = 1; $i < 255; ++$i) {
            $this->assertTrue($freq[$i] < $length / 255 * (1 + $delta) && $freq[$i] > $length / 255 * (1 - $delta));
        }
    }

    /**
     * @return RandomHelper
     */
    private function getRandomHelper()
    {
        return new RandomHelper();
    }
}
