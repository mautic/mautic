<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BooleanFieldIntegrationTest extends MauticMysqlTestCase
{
    public function testBooleanFieldFormSubmission(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new \Mautic\FormBundle\Entity\Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no' => 'Custom No',
        ]);

        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '0', $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom No', $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '', $field);
        $this->assertEquals(false, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, null, $field);
        $this->assertEquals(false, $result);
    }

    public function testBooleanFieldWithBlankLabels(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new \Mautic\FormBundle\Entity\Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no' => '',
        ]);

        $result = $normalizeValueMethod->invoke($submissionModel, ['1'], $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals(true, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, [''], $field);
        $this->assertEquals(false, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, [], $field);
        $this->assertEquals(false, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '', $field);
        $this->assertEquals(false, $result);

        $result = $normalizeValueMethod->invoke($submissionModel, null, $field);
        $this->assertEquals(false, $result);
    }
} 