<?php

/**
 * Regras de validação usadas em cadastro, perfil e redefinição de senha.
 */
final class Validator
{
    public static function digits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    public static function cpf(string $cpf): bool
    {
        $cpf = self::digits($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }
        return true;
    }

    /** Telefone com DDD: 10 dígitos (fixo) ou 11 dígitos começando com 9 (celular). */
    public static function phone(string $phone): bool
    {
        $d = self::digits($phone);
        if (strlen($d) === 11) {
            return $d[2] === '9' && (int) substr($d, 0, 2) >= 11;
        }
        return strlen($d) === 10 && (int) substr($d, 0, 2) >= 11;
    }

    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 150;
    }

    public static function date(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    public static function age(string $date): int
    {
        return (new DateTime($date))->diff(new DateTime('today'))->y;
    }

    /** Mesmas regras exibidas no formulário: 8+ caracteres, maiúscula, minúscula, número e símbolo. */
    public static function passwordProblems(string $password): array
    {
        $problems = [];
        if (mb_strlen($password) < 8) {
            $problems[] = 'ter pelo menos 8 caracteres';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $problems[] = 'ter uma letra maiúscula';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $problems[] = 'ter uma letra minúscula';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $problems[] = 'ter um número';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $problems[] = 'ter um símbolo (ex.: !@#$%)';
        }
        return $problems;
    }

    public static function passwordError(string $password, string $confirmation): ?string
    {
        $problems = self::passwordProblems($password);
        if ($problems) {
            return 'A senha precisa ' . implode(', ', $problems) . '.';
        }
        if ($password !== $confirmation) {
            return 'As senhas não coincidem.';
        }
        return null;
    }

    public static function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = str_replace(['R$', ' '], '', (string) $value);
        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        }
        return is_numeric($value) ? round((float) $value, 2) : -1.0;
    }
}
