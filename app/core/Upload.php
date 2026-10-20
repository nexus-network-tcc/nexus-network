<?php

/**
 * Upload de fotos de perfil: valida o conteúdo real do arquivo
 * e salva com nome aleatório e extensão definida pelo servidor.
 */
final class Upload
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /** Retorna null se nenhum arquivo foi enviado. Lança InvalidArgumentException se for inválido. */
    public static function photo(string $field = 'foto'): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                    ? 'A foto deve ter no máximo 5 MB.'
                    : 'Não foi possível enviar a foto. Tente novamente.'
            );
        }

        if ($file['size'] > Config::get('upload.max_size')) {
            throw new InvalidArgumentException('A foto deve ter no máximo 5 MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new InvalidArgumentException('Envie uma imagem JPG, PNG, WEBP ou GIF.');
        }

        $dir = Config::get('upload.dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new InvalidArgumentException('Não foi possível salvar a foto.');
        }

        return Config::get('upload.url') . '/' . $name;
    }

    /** Apaga uma foto enviada anteriormente (ignora URLs externas, como as do Google). */
    public static function delete(?string $path): void
    {
        if (!$path || !str_starts_with($path, Config::get('upload.url') . '/')) {
            return;
        }
        $file = ROOT_PATH . '/public/' . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
