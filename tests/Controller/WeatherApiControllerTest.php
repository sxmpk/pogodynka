<?php

namespace App\Tests\Controller;

use App\Entity\Measurement;
use App\Service\WeatherUtil;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WeatherApiControllerTest extends WebTestCase
{
    #[TestWith([''])]
    #[TestWith(['&format=json&twig=1'])]
    public function testIndex(string $querySuffix): void
    {
        $client = static::createClient();
        $measurement = (new Measurement())->setDate(new \DateTime('2026-12-01'))->setCelsius(12.5);
        $weatherUtil = $this->createMock(WeatherUtil::class);
        $weatherUtil->expects(self::once())
            ->method('getWeatherForCountryAndCity')
            ->with('PL', 'Łódź "Centrum"')
            ->willReturn([$measurement]);
        static::getContainer()->set(WeatherUtil::class, $weatherUtil);

        $client->request('GET', '/api/v1/weather?country=PL&city='.rawurlencode('Łódź "Centrum"').$querySuffix);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"city":"Łódź \"Centrum\"","country":"PL","measurements":[{"date":"2026-12-01","celsius":12.5,"fahrenheit":54.5}]}',
            $client->getResponse()->getContent(),
        );
    }

    public function testUnknownCityReturnsNotFound(): void
    {
        $client = static::createClient();
        $weatherUtil = $this->createMock(WeatherUtil::class);
        $weatherUtil->expects(self::once())
            ->method('getWeatherForCountryAndCity')
            ->willThrowException(new \InvalidArgumentException('Nie znaleziono podanej lokalizacji.'));
        static::getContainer()->set(WeatherUtil::class, $weatherUtil);

        $client->request('GET', '/api/v1/weather?country=PL&city=Nieznane');

        self::assertResponseStatusCodeSame(404);
    }

    #[TestWith([''])]
    #[TestWith(['&twig=1'])]
    public function testCsvHasOneRowPerMeasurement(string $querySuffix): void
    {
        $client = static::createClient();
        $weatherUtil = $this->createMock(WeatherUtil::class);
        $weatherUtil->expects(self::once())
            ->method('getWeatherForCountryAndCity')
            ->with('PL', 'Miasto, Test')
            ->willReturn([
                (new Measurement())->setDate(new \DateTime('2026-12-01'))->setCelsius(12.5),
                (new Measurement())->setDate(new \DateTime('2026-12-02'))->setCelsius(-1),
            ]);
        static::getContainer()->set(WeatherUtil::class, $weatherUtil);

        $client->request('GET', '/api/v1/weather?country=PL&city=Miasto%2C%20Test&format=csv'.$querySuffix);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
        self::assertSame(
            "city,country,date,celsius,fahrenheit\n\"Miasto, Test\",PL,2026-12-01,12.5,54.5\n\"Miasto, Test\",PL,2026-12-02,-1,30.2\n",
            $client->getResponse()->getContent(),
        );
    }
}
