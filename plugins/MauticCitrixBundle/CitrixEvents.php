<?php
/**
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle;

/**
 * Class CitrixEvents.
 *
 * Events available for MauticCitrixBundle
 */
final class CitrixEvents
{
    /**
     * The mautic.citrix_on_form_submit event is dispatched right before a form is submitted.
     *
     * The event listener receives a Mautic\FormBundle\Event\SubmissionEvent instance.
     *
     * @var string
     */
    const ON_FORM_SUBMIT_ACTION = 'mautic.citrix_on_form_submit';

    /**
     * The mautic.citrix_on_form_validate event is dispatched when a form is validated.
     *
     * The event listener receives a Mautic\FormBundle\Event\ValidationEvent instance.
     *
     * @var string
     */
    const ON_FORM_VALIDATE_ACTION = 'mautic.citrix_on_form_validate';
    
}
