<?php

/**
 * Login com Google via OAuth 2.0 (Authorization Code), sem dependências externas.
 */
final class GoogleOAuth
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public static function configured(): bool
    {
        return Config::get('google.client_id') && Config::get('google.client_secret');
    }

    public static function redirectUri(): string
    {
        return Config::get('google.redirect_uri') ?: absolute_url('auth/google/callback');
    }

    public static function authorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => Config::get('google.client_id'),
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);
    }

    /** @return array{sub: string, email: string, name: string, picture: ?string}|null */
    public static function fetchUser(string $code): ?array
    {
        $token = Http::postForm(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => Config::get('google.client_id'),
            'client_secret' => Config::get('google.client_secret'),
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
        ]);
        if (empty($token['access_token'])) {
            return null;
        }

        $info = Http::get(self::USERINFO_URL, ['Authorization: Bearer ' . $token['access_token']]);
        if (empty($info['sub']) || empty($info['email']) || empty($info['email_verified'])) {
            return null;
        }

        return [
            'sub'     => (string) $info['sub'],
            'email'   => mb_strtolower($info['email']),
            'name'    => $info['name'] ?? '',
            'picture' => $info['picture'] ?? null,
        ];
    }
}
