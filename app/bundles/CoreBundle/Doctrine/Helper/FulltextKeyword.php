<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Helper;

class FulltextKeyword
{
    private string $value;
    private bool $wordSearch;
    private bool $booleanMode;
    private bool $wordInflecting;

    public function __construct(string $value, bool $booleanMode = true, bool $wordSearch = true, bool $wordInflecting = false)
    {
        $this->value          = $value;
        $this->booleanMode    = $booleanMode;
        $this->wordSearch     = $wordSearch;
        $this->wordInflecting = $wordInflecting;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function format(): string
    {
        $return = '';
        $value  = mb_substr($this->value, 0, 255);

        if ($this->wordSearch) {
            $words     = explode(' ', preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $value));
            $wordCount = count($words);

            for ($i = 0; $i < $wordCount; ++$i) {
                $word = $words[$i];

                if ($this->booleanMode) {
                    // strip boolean operators
                    $word = str_replace(['+', '-', '@', '<', '>', '(', ')', '~', '*', '"'], '', $word);
                }

                $wordLength = mb_strlen($word);

                if ($wordLength > 0) {
                    if ($this->booleanMode) {
                        if ($this->wordInflecting && $wordLength > 3) {
                            $return .= '+('.$word.'* <'.mb_substr($word, 0, $wordLength - 1).'*)';
                        } else {
                            $return .= '+'.$word.'*';
                        }
                    } else {
                        $return .= $word;
                    }

                    $return .= ' ';
                }
            }

            $return = trim($return);
        }

        // append phrase search with a higher rank
        if ($this->booleanMode && $value) {
            $return = sprintf('%s"%s"', $return ? '('.$return.') >' : '', trim(str_replace('"', "'", $value)));
        }

        return $return;
    }
}
