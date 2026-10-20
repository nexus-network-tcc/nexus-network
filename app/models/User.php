<?php

final class User
{
    private const SELECT = 'SELECT u.*, p.profissao, p.descricao, p.preco_min, p.preco_max, p.verificado_em
                            FROM usuarios u
                            LEFT JOIN prestador_perfis p ON p.usuario_id = u.id';

    public static function find(int $id): ?array
    {
        return Database::first(self::SELECT . ' WHERE u.id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::first(self::SELECT . ' WHERE u.email = ?', [mb_strtolower(trim($email))]);
    }

    public static function findByGoogleId(string $googleId): ?array
    {
        return Database::first(self::SELECT . ' WHERE u.google_id = ?', [$googleId]);
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) Database::value(
            'SELECT 1 FROM usuarios WHERE email = ? AND id <> ?',
            [mb_strtolower(trim($email)), $exceptId ?? 0]
        );
    }

    public static function cpfTaken(string $cpf): bool
    {
        return (bool) Database::value('SELECT 1 FROM usuarios WHERE cpf = ?', [$cpf]);
    }

    /**
     * Cria o usuário e, se for prestador, o perfil profissional e as categorias.
     */
    public static function create(array $user, ?array $profile = null, array $categoryIds = []): int
    {
        return Database::transaction(static function () use ($user, $profile, $categoryIds) {
            $user['email'] = mb_strtolower(trim($user['email']));
            $id = Database::insert('usuarios', $user);
            if ($user['tipo'] === 'prestador' && $profile !== null) {
                Database::insert('prestador_perfis', ['usuario_id' => $id] + $profile);
                Category::syncForProvider($id, $categoryIds);
            }
            return $id;
        });
    }

    public static function update(int $id, array $data, ?array $profile = null, ?array $categoryIds = null): void
    {
        Database::transaction(static function () use ($id, $data, $profile, $categoryIds) {
            if ($data) {
                Database::update('usuarios', $data, 'id = :id', ['id' => $id]);
            }
            if ($profile !== null) {
                Database::update('prestador_perfis', $profile, 'usuario_id = :id', ['id' => $id]);
            }
            if ($categoryIds !== null) {
                Category::syncForProvider($id, $categoryIds);
            }
        });
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::update('usuarios', ['senha' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        // As chaves estrangeiras (ON DELETE CASCADE) removem perfil, avaliações, conversas e mensagens.
        Database::query('DELETE FROM usuarios WHERE id = ?', [$id]);
    }

    public static function counts(): array
    {
        return Database::first(
            "SELECT
                (SELECT COUNT(*) FROM usuarios WHERE tipo = 'prestador' AND suspenso_em IS NULL) AS prestadores,
                (SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente' AND suspenso_em IS NULL)   AS clientes,
                (SELECT COUNT(*) FROM avaliacoes)                        AS avaliacoes,
                (SELECT ROUND(AVG(nota), 1) FROM avaliacoes)             AS media,
                (SELECT COUNT(DISTINCT cidade, estado) FROM usuarios WHERE tipo = 'prestador' AND suspenso_em IS NULL) AS cidades"
        ) ?? [];
    }
}
