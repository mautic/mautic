<?php

namespace Mautic\EmailBundle\OptionsAccessor;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\UserBundle\Entity\User;

class EmailToUserAccessor
{
    private \Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer $transformer;

    public function __construct(
        private array $config
    ) {
        $this->transformer = new ArrayStringTransformer();
    }

    public function getEmailID(): int
    {
        return (int) $this->config['useremail']['email'];
    }

    /**
     * @return bool
     */
    public function shouldSentToOwner()
    {
        return empty($this->config['to_owner']) ? false : $this->config['to_owner'];
    }

    /**
     * Gets array of User ids formated for EmailModel.
     *
     * @param User|null $owner If Owner is passed in and config is setted for it, adds owner to returned array
     */
    public function getUserIdsToSend(User $owner = null): array
    {
        $userIds = empty($this->config['user_id']) ? [] : $this->config['user_id'];

        $users = [];
        if ($userIds) {
            foreach ($userIds as $userId) {
                $users[] = ['id' => $userId];
            }
        }

        if ($this->shouldSentToOwner() && $owner && !in_array($owner->getId(), $userIds)) {
            $users[] = ['id' => $owner->getId()];
        }

        return $users;
    }

    /**
     * @return array
     */
    public function getToFormatted()
    {
        $property = 'to';

        return empty($this->config[$property]) ? [] : $this->transformer->reverseTransform($this->config[$property]);
    }

    /**
     * @return array
     */
    public function getCcFormatted()
    {
        $property = 'cc';

        return empty($this->config[$property]) ? [] : $this->transformer->reverseTransform($this->config[$property]);
    }

    /**
     * @return array
     */
    public function getBccFormatted()
    {
        $property = 'bcc';

        return empty($this->config[$property]) ? [] : $this->transformer->reverseTransform($this->config[$property]);
    }
}
