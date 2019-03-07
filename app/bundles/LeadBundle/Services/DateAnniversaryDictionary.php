<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Services;

use Symfony\Component\Translation\TranslatorInterface;

class DateAnniversaryDictionary
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * DateAnniversaryDictionary constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return  [
            'anniversary' => $this->translator->trans('mautic.lead.list.anniversary'),
            'birthday'    => $this->translator->trans('mautic.lead.list.birthday'),
        ];
    }
}
