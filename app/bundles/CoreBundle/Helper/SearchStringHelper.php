<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Class SearchStringHelper.
 */
class SearchStringHelper
{
    const COMMAND_NEGATE  = 0;
    const COMMAND_POSIT   = 1;
    const COMMAND_NEUTRAL = 2;

    /**
     * @var array
     */
    protected $needsParsing = [
        ' ',
        '(',
        ')',
    ];

    /**
     * @var array
     */
    protected $needsClosing = [
        'quote'       => '"',
        'parenthesis' => '(',
    ];

    /**
     * @var array
     */
    protected $closingChars = [
        'quote'       => '"',
        'parenthesis' => ')',
    ];

    /**
     * SearchStringHelper constructor.
     *
     * @param array $needsParsing
     * @param array $needsClosing
     * @param array $closingChars
     */
    public function __construct(array $needsParsing = null, array $needsClosing = null, array $closingChars = null)
    {
        if (null !== $needsParsing) {
            $this->needsParsing = $needsParsing;
        }

        if (null !== $needsClosing) {
            $this->needsClosing = $needsClosing;
        }

        if (null !== $closingChars) {
            $this->closingChars = $closingChars;
        }
    }

    /**
     * @param string $input
     * @param array  $needsParsing
     * @param array  $needsClosing
     * @param array  $closingChars
     *
     * @return \stdClass
     */
    public static function parseSearchString($input, array $needsParsing = null, array $needsClosing = null, array $closingChars = null)
    {
        $input = trim(strip_tags($input));

        $self = new self($needsParsing, $needsClosing, $closingChars);

        return $self->parseString($input);
    }

    /**
     * @param $input
     */
    public function parseString($input)
    {
        return $this->splitUpSearchString($input);
    }

    /**
     * @param       $filters
     * @param array $commands
     */
    public static function mergeCommands(&$filters, array $commands)
    {
        if (!isset($filters->commands)) {
            $filters->commands = $commands;

            return;
        }

        foreach ($commands as $command => $status) {
            if (isset($filters->commands[$command])) {
                if ($status !== $filters->commands[$command]) {
                    $filters->commands[$command] = self::COMMAND_NEUTRAL;
                }
            } else {
                $filters->commands[$command] = $status;
            }
        }
    }

    /**
     * @param $filters
     * @param $mergeFilter
     */
    protected function addFilterCommand(&$filters, $mergeFilter)
    {
        $command = $mergeFilter->command;
        if ('is' === $command) {
            // Special case
            $command = $command.':'.$mergeFilter->string;
        }
        if (!empty($command)) {
            if (!isset($filters->commands[$command])) {
                $filters->commands[$command] = ($mergeFilter->not) ? self::COMMAND_NEGATE : self::COMMAND_POSIT;
            } else {
                if (($mergeFilter->not && self::COMMAND_POSIT === $filters->commands[$command]) || !$mergeFilter->not && self::COMMAND_NEGATE === $filters->commands[$command]) {
                    $filters->commands[$command] = self::COMMAND_NEUTRAL;
                }
            }
        }
    }

    /**
     * @param string $input
     * @param string $baseName
     * @param string $overrideCommand
     *
     * @return \stdClass
     */
    protected function splitUpSearchString($input, $baseName = 'root', $overrideCommand = '')
    {
        $keyCount                                 = 0;
        $command                                  = $overrideCommand;
        $filters                                  = new \stdClass();
        $filters->commands                        = [];
        $filters->{$baseName}                     = [];
        $filters->{$baseName}[$keyCount]          = new \stdClass();
        $filters->{$baseName}[$keyCount]->type    = 'and';
        $filters->{$baseName}[$keyCount]->command = $command;
        $filters->{$baseName}[$keyCount]->string  = '';
        $filters->{$baseName}[$keyCount]->not     = 0;
        $filters->{$baseName}[$keyCount]->strict  = 0;
        $chars                                    = str_split($input);
        $pos                                      = 0;
        $string                                   = '';

        //Iterate through every character to ensure that the search string is properly parsed from left to right while
        //considering quotes, parenthesis, and commands
        while (count($chars)) {
            $char = $chars[$pos];

            $string .= $char;
            unset($chars[$pos]);
            ++$pos;

            if ($char == ':') {
                //the string is a command
                $command = trim(substr($string, 0, -1));
                //does this have a negative?
                if (strpos($command, '!') === 0) {
                    $filters->{$baseName}[$keyCount]->not = 1;
                    $command                              = substr($command, 1);
                }

                if (empty($chars)) {
                    // Command hasn't been defined so don't allow empty or could end up searching entire table
                    unset($filters->{$baseName}[$keyCount]);
                } else {
                    $filters->{$baseName}[$keyCount]->command = $command;
                    $string                                   = '';
                }
            } elseif ($char == ' ') {
                //arrived at the end of a single word that is not within a quote or parenthesis so add it as standalone
                if ($string != ' ') {
                    $string = trim($string);
                    $type   = (strtolower($string) == 'or' || strtolower($string) == 'and') ? $string : '';
                    $this->setFilter($filters, $baseName, $keyCount, $string, $command, $overrideCommand, true, $type, (!empty($chars)));
                }
                continue;
            } elseif (in_array($char, $this->needsClosing)) {
                //arrived at a character that has a closing partner and thus needs to be parsed as a group

                //find the closing match
                $key = array_search($char, $this->needsClosing);

                $openingCount = 1;
                $closingCount = 1;

                //reiterate through the rest of the chars to find its closing match
                foreach ($chars as $k => $c) {
                    $string .= $c;
                    unset($chars[$k]);
                    ++$pos;

                    if ($c === $this->closingChars[$key] && $openingCount === $closingCount) {
                        //found the matching character (accounts for nesting)

                        //remove wrapping grouping chars
                        if (strpos($string, $char) === 0 && substr($string, -1) === $c) {
                            $string = substr($string, 1, -1);
                        }

                        //handle characters that support nesting
                        $neededParsing = false;
                        if ($c !== '"') {
                            //check to see if the nested string needs to be parsed as well
                            foreach ($this->needsParsing as $parseMe) {
                                if (strpos($string, $parseMe) !== false) {
                                    $parsed                                    = $this->splitUpSearchString($string, 'parsed', $command);
                                    $filters->{$baseName}[$keyCount]->children = $parsed->parsed;
                                    $neededParsing                             = true;
                                    break;
                                }
                            }
                        }

                        $this->setFilter($filters, $baseName, $keyCount, $string, $command, $overrideCommand, (!$neededParsing));

                        break;
                    } elseif ($c === $char) {
                        //this is another opening char so keep track of it to properly handle nested strings
                        ++$openingCount;
                    } elseif ($c === $this->closingChars[$key]) {
                        //this is a closing char within a nest but not the one to close the group
                        ++$closingCount;
                    }
                }
            } elseif (empty($chars)) {
                $filters->{$baseName}[$keyCount]->command = $command;
                $this->setFilter($filters, $baseName, $keyCount, $string, $command, $overrideCommand, true, null, false);
            }//else keep concocting chars
        }

        return $filters;
    }

    private function setFilter(&$filters, &$baseName, &$keyCount, &$string, &$command, $overrideCommand,
                                      $setFilter = true,
                                      $type = null,
                                      $setUpNext = true)
    {
        if (!empty($type)) {
            $filters->{$baseName}[$keyCount]->type = strtolower($type);
        } elseif ($setFilter) {
            $string = trim(strtolower($string));

            //remove operators and empty values
            if (in_array($string, ['', 'or', 'and'])) {
                unset($filters->{$baseName}[$keyCount]);

                return;
            }

            if (!isset($filters->{$baseName}[$keyCount]->strict)) {
                $filters->{$baseName}[$keyCount]->strict = 0;
            }
            if (!isset($filters->{$baseName}[$keyCount]->not)) {
                $filters->{$baseName}[$keyCount]->not = 0;
            }

            $strictPos = strpos($string, '+');
            $notPos    = strpos($string, '!');
            if (($strictPos === 0 || $strictPos === 1 || $notPos === 0 || $notPos === 1)) {
                if ($strictPos !== false && $notPos !== false) {
                    //+! or !+
                    $filters->{$baseName}[$keyCount]->strict = 1;
                    $filters->{$baseName}[$keyCount]->not    = 1;
                    $string                                  = substr($string, 2);
                } elseif ($strictPos === 0 && $notPos === false) {
                    //+
                    $filters->{$baseName}[$keyCount]->strict = 1;
                    $filters->{$baseName}[$keyCount]->not    = 0;
                    $string                                  = substr($string, 1);
                } elseif ($strictPos === false && $notPos === 0) {
                    //!
                    $filters->{$baseName}[$keyCount]->strict = 0;
                    $filters->{$baseName}[$keyCount]->not    = 1;
                    $string                                  = substr($string, 1);
                }
            }

            $filters->{$baseName}[$keyCount]->string = $string;

            $this->addFilterCommand($filters, $filters->{$baseName}[$keyCount]);

            //setup the next filter
            if ($setUpNext) {
                ++$keyCount;
                $filters->{$baseName}[$keyCount]          = new \stdClass();
                $filters->{$baseName}[$keyCount]->type    = 'and';
                $filters->{$baseName}[$keyCount]->command = $overrideCommand;
                $filters->{$baseName}[$keyCount]->string  = '';
                $filters->{$baseName}[$keyCount]->not     = 0;
                $filters->{$baseName}[$keyCount]->strict  = 0;
            }
        }
        $string  = '';
        $command = $overrideCommand;
    }
}
