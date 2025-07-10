<?php

namespace Mautic\CampaignBundle\EventCollector\Accessor\Event;

abstract class AbstractEventAccessor
{
    /**
     * @var array
     */
    protected $systemProperties = [
        'label',
        'description',
        'formType',
        'formTypeOptions',
        'formTheme',
        'timelineTemplate',
        'connectionRestrictions',
        'channel',
        'channelIdField',
    ];

    private array $extraProperties = [];

    public function __construct(
        protected array $config,
    ) {
        $this->filterExtraProperties();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->getProperty('label');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getProperty('description');
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->getProperty('formType');
    }

    /**
     * @return array
     */
    public function getFormTypeOptions()
    {
        return $this->getProperty('formTypeOptions', []);
    }

    /**
     * @return string
     */
    public function getFormTheme()
    {
        return $this->getProperty('formTheme');
    }

    /**
     * @return string
     */
    public function getTimelineTemplate()
    {
        return $this->getProperty('timelineTemplate');
    }

    /**
     * @return array
     */
    public function getConnectionRestrictions()
    {
        return $this->getProperty('connectionRestrictions', []);
    }

    /**
     * @return array
     */
    public function getExtraProperties()
    {
        return $this->extraProperties;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->getProperty('channel');
    }

    /**
     * @return mixed
     */
    public function getChannelIdField()
    {
        return $this->getProperty('channelIdField');
    }

    /**
     * @deprecated pre 2.13.0 support; to be removed in 3.0
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $property
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getProperty($property, $default = null)
    {
        return $this->config[$property] ?? $default;
    }

    /**
     * Calculate the difference in systemProperties and what was fed to the class.
     */
    private function filterExtraProperties(): void
    {
        $this->extraProperties = array_diff_key($this->config, array_flip($this->systemProperties));
    }
}
