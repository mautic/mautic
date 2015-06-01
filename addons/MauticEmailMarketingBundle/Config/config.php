<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Email Marketing',
    'description' => 'Enables integration with Mautic supported email marketing services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'services' => array(
        'forms' => array(
            'mautic.form.type.emailmarketing.mailchimp' => array(
                'class'     => 'MauticAddon\MauticEmailMarketingBundle\Form\Type\MailchimpType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailmarketing_mailchimp'
            ),
            'mautic.form.type.emailmarketing.constantcontact' => array(
                'class'     => 'MauticAddon\MauticEmailMarketingBundle\Form\Type\ConstantContactType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailmarketing_constantcontact'
            ),
            'mautic.form.type.emailmarketing.icontact' => array(
                'class'     => 'MauticAddon\MauticEmailMarketingBundle\Form\Type\IcontactType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailmarketing_icontact'
            )
        )
    )
);
