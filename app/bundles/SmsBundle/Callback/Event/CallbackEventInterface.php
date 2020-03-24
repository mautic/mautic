<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Callback\Event\AbstractCallbackEvent;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

interface CallbackEventInterface
{
    /**
     * @return ArrayCollection
     */
    public function getContacts();

    /**
     * @param ArrayCollection $contacts
     *
     * @return $this
     */
    public function setContacts(ArrayCollection $contacts);

    /**
     * @param Lead $contact
     *
     * @return $this
     */
    public function setContact(Lead $contact);
}
