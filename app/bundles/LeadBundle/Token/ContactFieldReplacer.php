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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Token\Match;
use Mautic\CoreBundle\Token\Replacer;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class ContactFieldReplacer.
 */
class ContactFieldReplacer extends Replacer
{
    private $tokenList = [];

    /** @var array */
    private $regex = ['{leadfield=(.*?)}', '{contactfield=(.*?)}'];

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * ContactFieldReplacer constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param Lead $contact
     * @param      $content
     * @param bool $replace
     *
     * @return mixed
     */
    public function findAndReplaceTokens(Lead $contact, $content, $replace = false)
    {
        /**
         * @var Match
         */
        foreach ($this->findTokens($content, $this->regex) as $token => $match) {
            if (isset($this->tokenList[$token])) {
                continue;
            }
            $this->tokenList[$token] = $this->getContactTokenValue(
                $contact->getProfileFields(),
                $match->getAlias(),
                $match->getModifier()
            );
        }

        if ($replace === false) {
            return $this->tokenList;
        }

        return str_replace(array_keys($this->tokenList), $this->tokenList, $content);
    }

    public function getContactTokenValue(array $fields, $alias, $modifier)
    {
        $value = '';
        if (isset($fields[$alias])) {
            $value = $fields[$alias];
        } elseif (isset($fields['companies'][0][$alias])) {
            $value = $fields['companies'][0][$alias];
        }

        if ($value) {
            switch ($modifier) {
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
                    switch ($modifier) {
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
        if (in_array($modifier, ['true', 'date', 'time', 'datetime'])) {
            return $value;
        } else {
            return $value ?: $modifier;
        }
    }
}
