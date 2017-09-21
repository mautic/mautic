<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\OptionsAccessor;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\UserBundle\Entity\User;

class EmailToUserAccessor
{
    /** @var array */
    private $config;

    /**
     * @var ArrayStringTransformer
     */
    private $transformer;

    public function __construct(array $config)
    {
        $this->config      = $config;
        $this->transformer = new ArrayStringTransformer();
    }

    /**
     * @return int
     */
    public function getEmailID()
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
     *
     * @return array
     */
    public function getUserIdsToSend(User $owner = null)
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
