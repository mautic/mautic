<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Entity;

use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use Mautic\CoreBundle\Helper\IntHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PointBundle\Entity\Point;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;

const MIN_INTEGER_VALUE = IntHelper::MIN_INTEGER_VALUE;
const MAX_INTEGER_VALUE = IntHelper::MAX_INTEGER_VALUE;

class PointEntityValidationTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider deltaScenariosProvider
     *
     * @throws MappingException
     */
    public function testDeltaValidationOnCreate(int $delta, string $errorMessage = ''): void
    {
        $crawler       = $this->client->request(Request::METHOD_GET, '/s/points/new');
        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();
        $form['point[name]']->setValue('Add point');
        $this->testPointData($form, $delta, $errorMessage);
    }

    /**
     * @dataProvider deltaScenariosProvider
     *
     * @throws MappingException
     */
    public function testDeltaValidationOnUpdate(int $delta, string $errorMessage = ''): void
    {
        $point = new Point();

        $point->setName('Edit point');
        $point->setDelta(5);
        $point->setType('form.submit');
        $point->setIsPublished(true);

        $this->em->persist($point);
        $this->em->flush();

        $pointId = $point->getId();

        $crawler       = $this->client->request(Request::METHOD_GET, '/s/points/edit/'.$pointId);
        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();
        $form['point[name]']->setValue('Point1');
        $this->testPointData($form, $delta, $errorMessage);
    }

    /**
     * @return iterable<string, array>
     *
     * @throws Exception
     */
    public function deltaScenariosProvider(): iterable
    {
        $acceptableDelta = random_int(MIN_INTEGER_VALUE, MAX_INTEGER_VALUE);
        yield 'within range' => [$acceptableDelta, ''];
        yield 'upper limit' => [MAX_INTEGER_VALUE, ''];
        yield 'lower limit' => [MIN_INTEGER_VALUE, ''];
        yield 'above upper limit' => [MAX_INTEGER_VALUE + 10, 'This value should be between -2147483648 and 2147483647.'];
        yield 'below lower limit' => [MIN_INTEGER_VALUE - 10, 'This value should be between -2147483648 and 2147483647.'];
    }

    /**
     * @throws MappingException
     */
    private function testPointData(Form $form, int $delta, string $errorMessage): void
    {
        $form['point[delta]']->setValue((string) $delta);
        $form['point[isPublished]']->setValue(true);
        $form['point[type]']->setValue('form.submit');

        $this->client->submit($form);
        self::assertTrue($this->client->getResponse()->isOk());

        $this->em->clear();

        $response = $this->client->getResponse()->getContent();
        self::assertStringContainsString($errorMessage, (string) $response);

        if ('' != $errorMessage) {
            $pointDetail = $this->em->getRepository(Point::class)->findOneBy(['delta' => $delta]);
            self::assertNull($pointDetail);
        }
    }
}
