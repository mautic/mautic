<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Token;

/**
 * abstract ReplacerBase.
 */
abstract class ReplacerBase implements ReplacerInterface
{
    /**
     * @var
     */
    private $tokenRegex;

    /**
     * @var
     */
    private $content;

    private $matches;

    private $tokenValue;

    public function __construct($regex, $content)
    {
        $this->regex   = $regex;
        $this->content = $content;
    }

    public function findTokens()
    {
        $this->tokenList = [];
        // convert string to array
        if (!is_array($this->getTokenRegex())) {
            $this->setTokenRegex([$this->getTokenRegex()]);
        }

        foreach ($this->getTokenRegex() as $regex) {
            $matches = $this->findMatches($regex);
            if (isset($foundMatches)) {
                foreach ($matches[2] as $key => $match) {
                    $token = $matches[0][$key];

                    if (isset($this->tokenList[$token])) {
                        continue;
                    }
                    $this->tokenList[$token] = $match;
                    //$alias        = $this->getFieldAlias();
                    //$defaultValue = self::getTokenDefaultValue($match);
                    //$tokenList[$token] = self::getTokenValue($lead, $alias, $defaultValue);
                }

                /*  if ($replace) {
                      $content = str_replace(array_keys($tokenList), $tokenList, $content);
                  }*/
            }
        }

        return $this->tokenList;
    }

    /**
     * @return mixed
     */
    public function findMatches($regex)
    {
        preg_match_all($regex, $this->getContent(), $matches);
        $this->setMatches($matches);
    }

    public function getFieldAlias($match)
    {
        $fallbackCheck = explode('|', $match);

        return $fallbackCheck[0];
    }

    /**
     * Returns correct token value from provided list of tokens and the concrete token.
     *
     * @param array  $tokens like ['{contactfield=website}' => 'https://mautic.org']
     * @param string $token  like '{contactfield=website|https://default.url}'
     *
     * @return string empty string if no match
     */
    public function getValueFromTokens(array $tokens, $token)
    {
        $token   = str_replace(['{', '}'], '', $token);
        $alias   = self::getFieldAlias($token);
        $default = self::getTokenDefaultValue($token);

        return empty($tokens["{{$alias}}"]) ? $default : $tokens["{{$alias}}"];
    }

    public function getTokenDefaultValue($match)
    {
        $fallbackCheck = explode('|', $match);
        if (!isset($fallbackCheck[1])) {
            return '';
        }

        return $fallbackCheck[1];
    }

    /**
     * @return mixed
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * @param mixed $matches
     */
    public function setMatches($matches)
    {
        $this->matches = $matches;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getTokenRegex()
    {
        return $this->tokenRegex;
    }

    /**
     * @param mixed $tokenRegex
     */
    public function setTokenRegex($tokenRegex)
    {
        $this->tokenRegex = $tokenRegex;
    }

    /**
     * @return mixed
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    /**
     * @param mixed $tokenValue
     */
    public function setTokenValue($tokenValue)
    {
        $this->tokenValue = $tokenValue;
    }
}
