<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Model;

use DOMDocument;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class GrapesJsBuilderModel extends AbstractCommonModel
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EmailModel
     */
    private $emailModel;

    public function __construct(RequestStack $requestStack, EmailModel $emailModel)
    {
        $this->requestStack = $requestStack;
        $this->emailModel   = $emailModel;
    }

    /**
     * @return GrapesJsBuilderRepository
     */
    public function getRepository()
    {
        /** @var GrapesJsBuilderRepository $repository */
        $repository = $this->em->getRepository(GrapesJsBuilder::class);

        $repository->setTranslator($this->translator);

        return $repository;
    }

    /**
     * Add or edit email settings entity based on request.
     */
    public function addOrEditEntity(Email $email)
    {
        if ($this->emailModel->isUpdatingTranslationChildren()) {
            return;
        }

        $grapesJsBuilder = $this->getRepository()->findOneBy(['email' => $email]);

        if (!$grapesJsBuilder) {
            $grapesJsBuilder = new GrapesJsBuilder();
            $grapesJsBuilder->setEmail($email);
        }

        if ($this->requestStack->getCurrentRequest()->request->has('grapesjsbuilder')) {
            $data = $this->requestStack->getCurrentRequest()->get('grapesjsbuilder', '');

            if (isset($data['customMjml'])) {
                $grapesJsBuilder->setCustomMjml($data['customMjml']);
            }

            $this->getRepository()->saveEntity($grapesJsBuilder);

            $customHtml = $this->requestStack->getCurrentRequest()->get('emailform')['customHtml'] ?? null;
            // TH CUSTOM - MARTECH-265: add UTM get parameters for all text-help links
            $email->setCustomHtml($this->generateUTMLinks($email, $customHtml));
            // END TH CUSTOM
            $this->emailModel->getRepository()->saveEntity($email);
        }
    }

    public function getGrapesJsFromEmailId(?int $emailId)
    {
        if ($email = $this->emailModel->getEntity($emailId)) {
            return $this->getRepository()->findOneBy(['email' => $email]);
        }
    }

    // TH CUSTOM - MARTECH-265: add UTM get parameters for all text-help links
    public function generateUTMLinks(Email $email, $utmHtml)
    {
        if ($email && !empty($utmHtml)) {
            $dom = new DOMDocument();

            // set error level
            $internalErrors = libxml_use_internal_errors(true);

            // load HTML
            $dom->loadHTML(mb_convert_encoding($utmHtml, 'HTML-ENTITIES', 'UTF-8'));

            // Restore error level
            libxml_use_internal_errors($internalErrors);

            $links = $dom->getElementsByTagName('a');

            foreach ($links as $link) {
                $currentLink         = $link->getAttribute('href');

                if (false !== strpos($currentLink, 'texthelp.com')) {
                    $this->addGtmTags($currentLink, $link, $email);
                }

                if (false !== strpos($currentLink, 'donjohnston.com')) {
                    $this->addGtmTags($currentLink, $link, $email);
                }

                if (false !== strpos($currentLink, 'wizzkids.tech')) {
                    $this->addGtmTags($currentLink, $link, $email);
                }

                if (false !== strpos($currentLink, 'lingit.no')) {
                    $this->addGtmTags($currentLink, $link, $email);
                }

                if (false !== strpos($currentLink, 'lexable.com')) {
                    $this->addGtmTags($currentLink, $link, $email);
                }

                if (false !== strpos($currentLink, 'clarosoftware.com')) {
                    $this->addGtmTags($currentLink, $link, $email);
                }
            }

            $utmHtml = $dom->saveHtml();
        }

        return (isset($utmHtml)) ? $utmHtml : null;
    }

    /**
     * If a link is found we attach the gtm tags to it.
     */
    private function addGtmTags($currentLink, $link, $email)
    {
        $newUTMLink = $link->cloneNode(true);

        // split url based on ?
        $bits = explode('?', $currentLink);
        // base url is first part of string
        $base_url = $bits[0];
        $get_vars = [];

        // url already contains get params
        if (isset($bits[1])) {
            $get_vars = explode('&', $bits[1]);
        }

        $newUTMUrl = $base_url;
        // setup utm campaign get params
        $linksToReplace[] = $currentLink;
        $get_vars[]       = 'utm_content='.$email->getSubject();
        $get_vars[]       = 'utm_medium='.'Email';
        $get_vars[]       = 'utm_source='.'Mautic';
        if ($email->getCategory()) {
            $get_vars[] = 'utm_campaign='.$email->getCategory()->getTitle();
        }

        // replace already existing utm values
        preg_match("/utm\_content|utm\_medium|utm\_source|utm\_campaign/i", $currentLink, $utmMatches);

        if (!empty($utmMatches)) {
            $newUTMUrl = preg_replace("/utm_content.+?(?=\&|\z)/", 'utm_content='.$email->getSubject(), $currentLink);
            $newUTMUrl = preg_replace("/utm_medium.+?(?=\&|\z)/", 'utm_medium='.'Email', $newUTMUrl);
            $newUTMUrl = preg_replace("/utm_source.+?(?=\&|\z)/", 'utm_source='.'Mautic', $newUTMUrl);
            if ($email->getCategory()) {
                $newUTMUrl = preg_replace("/utm_campaign.+?(?=\&|\z)/", 'utm_campaign='.$email->getCategory()->getTitle(), $newUTMUrl);
            }
        } else {
            // add new utm values to url
            $get_vars  = implode('&', $get_vars);
            $newUTMUrl = (strlen($get_vars) > 0) ? $base_url.'?'.$get_vars : $base_url;
        }

        $urlWithEncodedSpaces = preg_replace("/\s/", '%20', $newUTMUrl);

        $newUTMLink->setAttribute('href', $urlWithEncodedSpaces);
        $link->parentNode->replaceChild($newUTMLink, $link);
    }

    // END TH CUSTOM
}
