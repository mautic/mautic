<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Redirect;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

#[Group('database')]
final class PublicControllerTest extends MauticMysqlTestCase
{
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testGenerateActionWithContactTokenInLinkUrl(): void
    {
        $linkUrl = 'https://{contactfield=site_url}/tour';
        $focus   = new Focus();
        $focus->setName('Test');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'content' => [
                'headline'        => '',
                'link_text'       => 'Link text',
                'link_url'        => $linkUrl,
                'font'            => 'Arial, Helvetica, sans-serif',
                'link_new_window' => 1,
            ],
            'when'  => 'immediately',
            'modal' => [
                'placement' => 'top',
            ],
            'frequency' => 'everypage',
            'colors'    => [
                'primary'     => '#4e5d9d',
                'text'        => '#000000',
                'button'      => '#fdb933',
                'button_text' => '#ffffff',
            ],
        ]);
        $this->em->persist($focus);
        $this->em->flush();
        $this->em->clear();

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $content = $this->client->getResponse()->getContent();

        $redirects = $this->em->getRepository(Redirect::class)->findAll();
        $this->assertCount(1, $redirects);

        /** @var Redirect $redirect */
        $redirect = reset($redirects);
        $this->assertSame($linkUrl, $redirect->getUrl());

        $url  = $this->router->generate('mautic_url_redirect', ['redirectId' => $redirect->getRedirectId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $twig = $this->getContainer()->get(Environment::class);
        if (!$twig->hasExtension(EscaperExtension::class)) {
            $twig->addExtension(new EscaperExtension());
        }
        $url = $twig->getRuntime(EscaperRuntime::class)->escape($url, 'js');
        $this->assertStringContainsString($url, (string) $content);
    }
}
