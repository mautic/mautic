<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Event\Service;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Event\SubmissionEvent;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

final class FieldValueTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransformValuesAfterSubmitWithNoFieldsNoMatchesAndNoTokens(): void
    {
        $router = new class() extends Router {
            public function __construct()
            {
            }
        };
        $transformer     = new FieldValueTransformer($router);
        $submission      = new Submission();
        $form            = new Form();
        $request         = new Request();
        $submissionEvent = new SubmissionEvent($submission, [], [], $request);
        $submission->setForm($form);
        $transformer->transformValuesAfterSubmit($submissionEvent);

        Assert::assertSame([], $submissionEvent->getTokens());
        Assert::assertSame([], $submissionEvent->getContactFieldMatches());
    }

    public function testTransformValuesAfterSubmitWithFileFieldMatchesAndTokens(): void
    {
        $router                               = new class() extends Router {
            public $generateMethodCallCounter = 0;

            public function __construct()
            {
            }

            public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
            {
                Assert::assertSame('mautic_form_file_download', $name);
                Assert::assertSame([
                    'submissionId' => 456,
                    'field'        => 'file_field_1',
                ], $parameters);
                Assert::assertSame(self::ABSOLUTE_PATH, $referenceType);
                ++$this->generateMethodCallCounter;

                return 'generated/route';
            }
        };
        $transformer = new FieldValueTransformer($router);
        $submission  = new class() extends Submission {
            public function getId()
            {
                return 456;
            }
        };
        $form            = new Form();
        $field           = new Field();
        $request         = new Request();
        $submissionEvent = new SubmissionEvent($submission, [], [], $request);
        $field->setType('file');
        $field->setAlias('file_field_1');
        $field->setMappedField('contact_field_1');
        $field->setMappedObject('lead');
        $form->addField('123', $field);
        $submission->setForm($form);
        $submissionEvent->setTokens(['{formfield=file_field_1}' => 'original/route']);
        $submissionEvent->setContactFieldMatches(['contact_field_1' => 'original/route']);
        $transformer->transformValuesAfterSubmit($submissionEvent);

        Assert::assertSame(['{formfield=file_field_1}' => 'generated/route'], $submissionEvent->getTokens());
        Assert::assertSame(['{formfield=file_field_1}' => 'generated/route'], $transformer->getTokensToUpdate());
        Assert::assertSame(['contact_field_1' => 'generated/route'], $submissionEvent->getContactFieldMatches());
        Assert::assertSame(['contact_field_1' => 'generated/route'], $transformer->getContactFieldsToUpdate());

        // Calling it for the second time to ensure it's executed only once.
        $transformer->transformValuesAfterSubmit($submissionEvent);
        Assert::assertSame(1, $router->generateMethodCallCounter);
    }
}
