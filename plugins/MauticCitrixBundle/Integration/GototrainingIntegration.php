<?php

namespace MauticPlugin\MauticCitrixBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GototrainingIntegration extends CitrixAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Gototraining';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'GoToTraining';
    }
}
