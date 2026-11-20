<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TextInputControllerTest extends WebTestCase
{
    public function testSubmittedTextIsDisplayed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/text/input');
        self::assertResponseIsSuccessful();

        $client->submitForm('Wyślij', ['text_input[text]' => '<strong>Pogodynka</strong>']);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('#submitted-text', '<strong>Pogodynka</strong>');
        self::assertSelectorNotExists('#submitted-text strong');
        self::assertSelectorTextSame('#escaped-text', '&lt;strong&gt;Pogodynka&lt;/strong&gt;');
        self::assertStringContainsString('<p id="submitted-text">&lt;strong&gt;Pogodynka&lt;/strong&gt;</p>', $client->getResponse()->getContent());
    }
}
