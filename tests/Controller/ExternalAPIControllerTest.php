<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExternalAPIControllerTest extends WebTestCase
{
    public function testForecastUsesSubmittedCoordinates(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        static::getContainer()->set(HttpClientInterface::class, new MockHttpClient(
            static function (string $method, string $url): JsonMockResponse {
                self::assertSame('GET', $method);
                self::assertStringStartsWith('https://api.open-meteo.com/v1/forecast?', $url);
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                self::assertSame([
                    'latitude' => '53.429',
                    'longitude' => '14.553',
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
                    'timezone' => 'UTC',
                ], $query);

                return new JsonMockResponse(['daily' => [
                    'time' => ['2026-11-20'],
                    'temperature_2m_max' => [9.5],
                    'temperature_2m_min' => [-1.2],
                    'precipitation_sum' => [0],
                ]]);
            },
        ));

        $client->request('GET', '/externalapi');
        self::assertResponseIsSuccessful();
        $client->submitForm('Pobierz prognozę', [
            'external_api[latitude]' => '53.429',
            'external_api[longitude]' => '14.553',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['2026-11-20', '9.5', '-1.2', '0'], $client->getCrawler()->filter('tbody tr > *')->each(
            static fn ($cell): string => $cell->text(),
        ));
    }
}
