<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Vérification des jetons Cloudflare Turnstile (API siteverify).
 *
 * @see https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 */
final class TurnstileVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $secretKey,
    ) {
    }

    public function verify(string $responseToken, ?string $remoteIp = null): bool
    {
        if ($responseToken === '' || !$this->secretKey) {
            return false;
        }

        $body = [
            'secret' => $this->secretKey,
            'response' => $responseToken,
        ];
        if ($remoteIp !== null && $remoteIp !== '') {
            $body['remoteip'] = $remoteIp;
        }

        try {
            $r = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => $body,
                'timeout' => 10,
            ]);
            $data = $r->toArray(false);

            return ($data['success'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
