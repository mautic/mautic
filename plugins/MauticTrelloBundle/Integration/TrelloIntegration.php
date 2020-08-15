<?php

declare(strict_types=1);
/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticTrelloBundle\Integration;

// use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class TrelloIntegration.
 *
 * Handles the authorization process, integration configuration, etc.
 */
class TrelloIntegration extends AbstractIntegration
{
    /**
     * Check if plugin is published.
     */
    public function isPublished(): bool
    {
        return $this->getIntegrationSettings()->getIsPublished();
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Trello';
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'appkey'      => 'mautic.trello.integration.appkey',
            'apitoken'    => 'mautic.trello.integration.apitoken',
        ];
    }

    /**
     * Configure the name of the secret key.
     */
    public function getSecretKeys(): array
    {
        return [
            'apitoken',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @todo  parent::getFormNotes should have the documented return type array
     *
     * @return array\string
     */
    public function getFormNotes($section): array
    {
        $translator = $this->getTranslator();

        if ('authorization' === $section) {
            return [
                $translator->trans('mautic.trello.integration.info'),
                'info',
            ];
        }

        return parent::getFormNotes($section);
    }
}
