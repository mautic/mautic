<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Symfony\Component\Form\FormInterface;

interface FormAdjustmentsProviderInterface
{
    /**
     * Allows subscribers to adjust a form so new fields can be added, deleted or modified.
     */
    public function adjustForm(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails): FormInterface;
}
