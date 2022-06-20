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
use Symfony\Component\HttpFoundation\Response;

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
     */
    public function testDeltaValidationOnCreateViaAPI(int $delta, string $errorMessage = ''): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/points/new',
            [
                'name'        => 'Point1',
                'delta'       => $delta,
                'isPublished' => true,
                'type'        => 'form.submit',
            ]
        );

        $response = $this->client->getResponse();

        if ($errorMessage) {
            self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), $response->getContent());
            self::assertStringContainsString('error', $response->getContent());
            self::assertStringContainsString($errorMessage, $response->getContent());
        } else {
            self::assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());
            self::assertStringNotContainsString('error', $response->getContent());
        }
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
        $form['point[name]']->setValue('Edit point');
        $this->testPointData($form, $delta, $errorMessage);
    }

    /**
     * @return iterable<string, array<mixed>>
     *
     * @throws Exception
     */
    public function deltaScenariosProvider(): iterable
    {
        yield 'within range positive number' => [3000, ''];
        yield 'within range negative number' => [-7857, ''];
        yield 'within range zero' => [0, ''];
        yield 'upper limit' => [IntHelper::MAX_INTEGER_VALUE, ''];
        yield 'lower limit' => [IntHelper::MIN_INTEGER_VALUE, ''];
        yield 'above upper limit' => [IntHelper::MAX_INTEGER_VALUE + 10, 'This value should be between -2147483648 and 2147483647.'];
        yield 'below lower limit' => [IntHelper::MIN_INTEGER_VALUE - 10, 'This value should be between -2147483648 and 2147483647.'];
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

        $pointDetail = $this->em->getRepository(Point::class)->findOneBy(['delta' => $delta]);
        '' == $errorMessage ? self::assertNotNull($pointDetail) : self::assertNull($pointDetail);
    }
}
