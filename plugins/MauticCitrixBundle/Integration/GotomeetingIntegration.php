<?php

namespace MauticPlugin\MauticCitrixBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GotomeetingIntegration extends CitrixAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Gotomeeting';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'GoToMeeting';
    }
}
