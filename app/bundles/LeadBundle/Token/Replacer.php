<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Token;

use Mautic\CoreBundle\Token\ReplacerBase;

/**
 * Class Replacer.
 */
class Replacer extends ReplacerBase
{
    private $tokenList = [];

    public function findAndReplaceTokens($content)
    {
        $contact = [];
        $tokens  = $this->findTokens();
        $replace = [];
        foreach ($tokens as $token => $match) {
            $replace[$token] = $this->getContactTokenValue(
                $contact,
                $this->getFieldAlias($match),
                $this->getTokenDefaultValue($match)
            );
        }

        $content = str_replace(array_keys($replace), $replace, $content);
    }

    public function getContactTokenValue(array $contact, $alias, $defaultValue)
    {
        $value = '';
        if (isset($contact[$alias])) {
            $value = $contact[$alias];
        } elseif (isset($contact['companies'][0][$alias])) {
            $value = $contact['companies'][0][$alias];
        }

        if ($value) {
            switch ($defaultValue) {
                case 'true':
                    $value = urlencode($value);
                    break;
                case 'datetime':
                case 'date':
                case 'time':
                    $dt   = new DateTimeHelper($value);
                    $date = $dt->getDateTime()->format(
                        (new ParamsLoaderHelper())->getParameters()['date_format_dateonly']
                    );
                    $time = $dt->getDateTime()->format(
                        (new ParamsLoaderHelper())->getParameters()['date_format_timeonly']
                    );
                    switch ($defaultValue) {
                        case 'datetime':
                            $value = $date.' '.$time;
                            break;
                        case 'date':
                            $value = $date;
                            break;
                        case 'time':
                            $value = $time;
                            break;
                    }
                    break;
            }
        }
        if (in_array($defaultValue, ['true', 'date', 'time', 'datetime'])) {
            return $value;
        } else {
            return $value ?: $defaultValue;
        }
    }
}
