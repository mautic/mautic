<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Submission;

/**
 * Class SubmissionEvent
 */
class SubmissionEvent extends CommonEvent
{

    /**
     * @var array
     */
    private $post;

    /**
     * @var array
     */
    private $server;

    /**
     * @param Submission $submission
     * @param array $post
     * @param array $server
     */
    public function __construct(Submission $submission, $post, $server)
    {
        $this->entity = $submission;
        $this->post   = $post;
        $this->server = $server;
    }

    /**
     * Returns the Submission entity
     *
     * @return Submission
     */
    public function getSubmission()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return array
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return \Mautic\FormBundle\Entity\Form
     */
    public function getForm()
    {
        return $this->entity->getForm();
    }
}
