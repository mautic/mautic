<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class EmailSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{

    private $leadFieldRegex = '{leadfield=(.*?)}';

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailDisplay', 0)
        );
    }

    public function onEmailBuild(EmailBuilderEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'lead.field', 'lead:fields', 'MauticLeadBundle');
        $tokenHelper->setPermissionSet(array('lead:fields:full'));

        if ($event->tokenSectionsRequested()) {
            //add email tokens
            $event->addTokenSection(
                'lead.emailtokens',
                'mautic.lead.email.header.index',
                $tokenHelper->getTokenContent(
                    array(
                        'filter' => array(
                            'force' => array(
                                array(
                                    'column' => 'f.isPublished',
                                    'expr'   => 'eq',
                                    'value'  => true
                                )
                            )
                        ),
                        'orderBy'        => 'f.label',
                        'orderByDir'     => 'ASC',
                        'hydration_mode' => 'HYDRATE_ARRAY'
                    )
                ),
                255
            );
        }

        if ($event->tokensRequested($this->leadFieldRegex)) {
            $event->addTokensFromHelper($tokenHelper, $this->leadFieldRegex, 'label', 'alias', true);
        }
    }

    public function onEmailDisplay(EmailSendEvent $event)
    {
        if ($this->factory->getSecurity()->isAnonymous()) {
            $this->onEmailGenerate($event);
        } //else this is a user previewing so leave lead fields tokens in place
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content = $event->getContent();
        $regex   = '/' . $this->leadFieldRegex . '/';

        $lead = $event->getLead();

        preg_match_all($regex, $content, $matches);
        if (!empty($matches[1])) {
            $tokenList = array();
            foreach ($matches[1] as $key => $match) {
                $token = $matches[0][$key];

                if (isset($tokenList[$token])) {
                    continue;
                }

                $fallbackCheck = explode('|', $match);
                $fallback      = $urlencode = false;

                if (isset($fallbackCheck[1])) {
                    // There is a fallback or to be urlencoded
                    $alias = $fallbackCheck[0];

                    if ($fallbackCheck[1] === 'true') {
                        $urlencode = true;
                        $fallback  = '';
                    } else {
                        $fallback = $fallbackCheck[1];
                    }
                } else {
                    $alias = $match;
                }

                $value             = (!empty($lead[$alias])) ? $lead[$alias] : $fallback;
                $tokenList[$token] = ($urlencode) ? urlencode($value) : $value;
            }

            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }
}