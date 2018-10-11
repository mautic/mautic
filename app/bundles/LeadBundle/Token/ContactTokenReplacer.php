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
use Mautic\CoreBundle\Token\TokenReplacer;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class ContactTokenReplacer.
 */
class ContactTokenReplacer extends TokenReplacer
{
    private $tokenList = [];

    /** @var array */
    private $regex = ['/({|%7B)leadfield=(.*?)(}|%7D)/', '/({|%7B)contactfield=(.*?)(}|%7D)/'];

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
     * @param string          $content
     * @param array|Lead|null $contact
     *
     * @return array
     */
    public function findTokens($content, $contact = null)
    {
        foreach ($this->searchTokens($content, $this->regex) as $token => $tokenAttribute) {
            if (isset($this->tokenList[$token])) {
                continue;
            }
            $this->tokenList[$token] = $this->getContactTokenValue(
                $contact instanceof Lead ? $contact->getProfileFields() : $contact,
                $tokenAttribute->getAlias(),
                $tokenAttribute->getModifier()
            );
        }

        return $this->tokenList;
    }

    /**
     * @param array $fields
     * @param       $alias
     * @param       $modifier
     *
     * @return mixed|string
     */
    private function getContactTokenValue(array $fields, $alias, $modifier)
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
                        $this->coreParametersHelper->getParameter('date_format_dateonly')
                    );
                    $time = $dt->getDateTime()->format(
                        $this->coreParametersHelper->getParameter('date_format_timeonly')
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
