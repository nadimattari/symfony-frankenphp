<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CspHeadersTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHomePageHasStrictPolicyWithNonceMatchingTheInlineScript(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $nonce = $this->extractNonce((string) $response->getContent());

        $policy = $response->headers->get('Content-Security-Policy');
        self::assertNotNull($policy, 'Every response must carry a Content-Security-Policy header.');
        self::assertSame($this->expectedPolicy($nonce), $policy);

        $scriptSrc = explode(';', $policy)[1];
        self::assertStringNotContainsString("'unsafe-eval'", $policy);
        self::assertStringNotContainsString("'unsafe-inline'", $scriptSrc);
        self::assertDoesNotMatchRegularExpression('/https?:\/\/|\*/', $scriptSrc);
    }

    public function testNonceIsFreshAndHighEntropyPerRequest(): void
    {
        $first = $this->requestNonce();
        $second = $this->requestNonce();

        self::assertNotSame($first, $second, 'Nonces must not be reused across responses.');
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $first);
        self::assertSame(18, strlen(base64_decode(strtr($first, '-_', '+/'), true) ?: ''));
    }

    public function testJsonApiResponseAlsoCarriesThePolicy(): void
    {
        $this->client->request('GET', '/api/demo/products', [], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertNotNull($this->client->getResponse()->headers->get('Content-Security-Policy'));
    }

    private function requestNonce(): string
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();

        return $this->extractNonce((string) $this->client->getResponse()->getContent());
    }

    private function extractNonce(string $html): string
    {
        $pattern = '/<script nonce="(?<nonce>[A-Za-z0-9_-]{24})">/';
        self::assertMatchesRegularExpression($pattern, $html, 'The inline globals script must carry a nonce.');
        preg_match($pattern, $html, $matches);

        return $matches['nonce'];
    }

    private function expectedPolicy(string $nonce): string
    {
        return sprintf(
            "default-src 'self'; script-src 'self' 'nonce-%s'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'",
            $nonce,
        );
    }
}
