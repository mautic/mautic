<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class FormFieldHelperFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function testFormFieldsWithParameterizedUrl(): void
    {
        $form = new Form();
        $form->setIsPublished(true);
        $form->setName('Form Test URL');
        $form->setAlias('formtesturl');
        $form->setFormType('standalone');
        $this->em->persist($form);
        $this->em->flush();
        $formId = $form->getId();

        $field = new Field();
        $field->setForm($form);
        $field->setLabel('email');
        $field->setAlias('email');
        $field->setType('email');
        $this->em->persist($field);
        $this->em->flush();

        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle('Page A');
        $page->setAlias('page-a');
        $page->setTemplate('blank');
        $page->setCustomHtml(
            '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" lang="en">
                <head>
                    <title>{pagetitle}</title>
                    <meta name="description" content="{pagemetadescription}" />
                </head>
                <body class="ui-sortable">
                    <div data-section-wrapper="1">
                        <div data-slot-container="1" style="min-height: 30px" class="ui-sortable">
                            <div data-slot="text" class="">{form='.$formId.'}</div>
                        </div>
                    </div>
                </body>
            </html>'
        );
        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        $crawler    = $this->client->request(Request::METHOD_GET, '/page-a?email=test@example.com');
        $inputValue = $crawler->filter('input[type=email]')->attr('value');
        self::assertSame('test@example.com', $inputValue);
    }
}
