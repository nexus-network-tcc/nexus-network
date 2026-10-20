<?php

/**
 * Galeria de fotos de trabalhos do prestador.
 */
final class Gallery
{
    public const MAX_PHOTOS = 12;

    public static function forProvider(int $providerId): array
    {
        try {
            return Database::all('SELECT id, caminho, legenda FROM prestador_fotos WHERE prestador_id = ? ORDER BY id DESC', [$providerId]);
        } catch (PDOException) {
            return []; // banco ainda sem a migração 003
        }
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM prestador_fotos WHERE id = ?', [$id]);
    }

    public static function count(int $providerId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM prestador_fotos WHERE prestador_id = ?', [$providerId]);
    }

    public static function add(int $providerId, string $path, ?string $caption): void
    {
        Database::insert('prestador_fotos', [
            'prestador_id' => $providerId,
            'caminho'      => $path,
            'legenda'      => $caption,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM prestador_fotos WHERE id = ?', [$id]);
    }

    /** Apaga os arquivos do disco (as linhas saem em cascata junto com o usuário). */
    public static function deleteFiles(int $providerId): void
    {
        try {
            foreach (self::forProvider($providerId) as $photo) {
                Upload::delete($photo['caminho']);
            }
        } catch (PDOException) {
            // sem a tabela não há arquivos a apagar
        }
    }
}
