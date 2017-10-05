<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Helper;

use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DynamicContentHelper
{
    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var DynamicContentModel
     */
    protected $dynamicContentModel;

    /**
     * DynamicContentHelper constructor.
     *
     * @param DynamicContentModel      $dynamicContentModel
     * @param EventModel               $campaignEventModel
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(DynamicContentModel $dynamicContentModel, EventModel $campaignEventModel, EventDispatcherInterface $dispatcher)
    {
        $this->dynamicContentModel = $dynamicContentModel;
        $this->campaignEventModel  = $campaignEventModel;
        $this->dispatcher          = $dispatcher;
    }

    /**
     * @param            $slot
     * @param Lead|array $lead
     *
     * @return string
     */
    public function getDynamicContentForLead($slot, $lead)
    {
        $response = $this->campaignEventModel->triggerEvent('dwc.decision', $slot, 'dwc.decision.'.$slot);
        $content  = '';

        if (is_array($response) && !empty($response['action']['dwc.push_content'])) {
            $content = array_shift($response['action']['dwc.push_content']);
        } else {
            $data = $this->dynamicContentModel->getSlotContentForLead($slot, $lead);

            if (!empty($data)) {
                $content = $data['content'];
                $dwc     = $this->dynamicContentModel->getEntity($data['id']);
                if ($dwc instanceof DynamicContent) {
                    $content = $this->getRealDynamicContent($slot, $lead, $dwc);
                }
            }
        }

        return $content;
    }

    /**
     * @param string     $content
     * @param Lead|array $lead
     *
     * @return string Content with the {content} tokens replaced with dynamic content
     */
    public function replaceTokensInContent($content, $lead)
    {
        // Find all dynamic content tags
        preg_match_all('/{(dynamiccontent)=(\w+)(?:\/}|}(?:([^{]*(?:{(?!\/\1})[^{]*)*){\/\1})?)/is', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $slot           = $match[2];
            $defaultContent = $match[3];

            $dwcContent = $this->getDynamicContentForLead($slot, $lead);

            if (!$dwcContent) {
                $dwcContent = $defaultContent;
            }

            $content = str_replace($matches[0], $dwcContent, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     * @param Lead   $lead
     *
     * @return array
     */
    public function findDwcTokens($content, Lead $lead)
    {
        preg_match_all('/{dwc=(.*?)}/', $content, $matches);

        $tokens = [];
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $dwcId) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $dwc = $this->dynamicContentModel->getEntity($dwcId);

                if (!$dwc || $dwc->getIsCampaignBased()) {
                    continue;
                }

                $content = $this->getRealDynamicContent($dwc->getName(), $lead, $dwc);

                $tokens[$token]['content'] = $content;
                $tokens[$token]['filters'] = $dwc->getFilters();
            }

            unset($matches);
        }

        return $tokens;
    }

    /**
     * @param $slot
     * @param $lead
     * @param $dwc
     *
     * @return string
     */
    public function getRealDynamicContent($slot, $lead, DynamicContent $dwc)
    {
        $content = $dwc->getContent();
        // Determine a translation based on contact's preferred locale
        /** @var DynamicContent $translation */
        list($ignore, $translation) = $this->dynamicContentModel->getTranslatedEntity($dwc, $lead);
        if ($translation !== $dwc) {
            // Use translated version of content
            $dwc     = $translation;
            $content = $dwc->getContent();
        }
        $this->dynamicContentModel->createStatEntry($dwc, $lead, $slot);

        $tokenEvent = new TokenReplacementEvent($content, $lead, ['slot' => $slot, 'dynamic_content_id' => $dwc->getId()]);
        $this->dispatcher->dispatch(DynamicContentEvents::TOKEN_REPLACEMENT, $tokenEvent);

        return $tokenEvent->getContent();
    }
}
