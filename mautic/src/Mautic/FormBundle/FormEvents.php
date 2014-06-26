<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle;

/**
 * Class FormEvents
 * Events available for FormBundle
 *
 * @package Mautic\FormBundle
 */
final class FormEvents
{
    /**
     * The mautic.form_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a
     * Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_PRE_SAVE   = 'mautic.form_pre_save';

    /**
     * The mautic.form_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a
     * Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_POST_SAVE   = 'mautic.form_post_save';

    /**
     * The mautic.form_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a
     * Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_PRE_DELETE   = 'mautic.form_pre_delete';

    /**
     * The mautic.form_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a
     * Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_POST_DELETE   = 'mautic.form_post_delete';

    /**
     * The mautic.form_on_build event is thrown before displaying the form builder form to allow adding of custom form
     * fields and submit actions
     *
     * The event listener receives a
     * Mautic\FormBundle\Event\FormBuilderEvent instance.
     *
     * @var string
     */
    const FORM_ON_BUILD   = 'mautic.form_on_build';
}