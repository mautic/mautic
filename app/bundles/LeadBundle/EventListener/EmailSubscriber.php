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

    private static $leadFieldRegex = '{leadfield=(.*?)}';

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

    /**
     * @param EmailBuilderEvent $event
     */
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

        if ($event->tokensRequested(self::$leadFieldRegex)) {
            $event->addTokensFromHelper($tokenHelper, self::$leadFieldRegex, 'label', 'alias', true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailDisplay(EmailSendEvent $event)
    {
        $this->onEmailGenerate($event);
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content = $event->getContent();
        $lead    = $event->getLead();

        $tokenList = self::findLeadTokens($content, $lead);
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }

    /**
     * @param string $content
     * @param array  $lead
     * @param bool   $replace If true, search/replace will be executed on $content and the modified $content returned
     *                        rather than an array of found matches
     *
     * @return array|string
     */
    static function findLeadTokens($content, $lead, $replace = false)
    {
        // Search for bracket or bracket encoded
        $regex     = '/({|%7B)leadfield=(.*?)(}|%7D)/';
        $tokenList = array();

        $foundMatches = preg_match_all($regex, $content, $matches);
        if ($foundMatches) {
            foreach ($matches[2] as $key => $match) {
                $token = $matches[0][$key];

                if (isset($tokenList[$token])) {
                    continue;
                }

                $fallbackCheck = explode('|', $match);
                $urlencode     = false;
                $fallback      = '';

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

            if ($replace) {
                $content = str_replace(array_keys($tokenList), $tokenList, $content);
            }
        }

        return $replace ? $content : $tokenList;
    }
}