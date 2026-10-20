<?php

/**
 * Cliente HTTP simples (cURL) para as APIs externas: Google e Nominatim.
 */
final class Http
{
    public static function get(string $url, array $headers = []): ?array
    {
        return self::request('GET', $url, null, $headers);
    }

    public static function postForm(string $url, array $fields): ?array
    {
        return self::request('POST', $url, http_build_query($fields), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    private static function request(string $method, string $url, ?string $body, array $headers): ?array
    {
        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => 'NexusNetwork/2.0 (' . Config::get('support_email') . ')',
            CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
            CURLOPT_SSL_VERIFYPEER => Config::get('ssl.verify'),
            CURLOPT_SSL_VERIFYHOST => Config::get('ssl.verify') ? 2 : 0,
        ];
        if (is_file(Config::get('ssl.ca'))) {
            $options[CURLOPT_CAINFO] = Config::get('ssl.ca');
        }
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("HTTP {$method} {$url} falhou: {$error}");
            return null;
        }

        $data = json_decode($response, true);
        if ($status >= 400) {
            error_log("HTTP {$method} {$url} retornou {$status}");
        }
        return is_array($data) ? $data + ['_status' => $status] : null;
    }
}
