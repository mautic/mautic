<?php

namespace Mautic\CoreBundle\Helper\RandomHelper;

/**
 * Class RandomHelper.
 */
final class RandomHelper implements RandomHelperInterface
{
    /**
     * Based on \Nette\Utils\Random.
     *
     * @param int    $length
     * @param string $charlist
     *
     * @return string
     */
    public function generate($length = 10, $charlist = '0-9a-z')
    {
        $charlist = count_chars(preg_replace_callback('#.-.#', function (array $m) {
            return implode('', range($m[0][0], $m[0][2]));
        }, $charlist), 3);
        $chLen = strlen($charlist);
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be greater than zero.');
        } elseif ($chLen < 2) {
            throw new \InvalidArgumentException('Character list must contain as least two chars.');
        }
        $res = '';
        for ($i = 0; $i < $length; ++$i) {
            $res .= $charlist[random_int(0, $chLen - 1)];
        }

        return $res;
    }
}
