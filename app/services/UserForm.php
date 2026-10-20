<?php

/**
 * Validação compartilhada entre cadastro e edição de perfil.
 */
final class UserForm
{
    /**
     * Campos pessoais editáveis (nome, telefone, gênero, estado, cidade).
     * @return array{0: array, 1: array} [dados, erros]
     */
    public static function personal(array $in): array
    {
        $errors = [];
        $data = [
            'nome'     => preg_replace('/\s+/', ' ', trim((string) ($in['nome'] ?? ''))),
            'telefone' => Validator::digits((string) ($in['telefone'] ?? '')),
            'genero'   => (string) ($in['genero'] ?? ''),
            'estado'   => strtoupper(trim((string) ($in['estado'] ?? ''))),
            'cidade'   => trim((string) ($in['cidade'] ?? '')),
        ];

        if (mb_strlen($data['nome']) < 3 || mb_strlen($data['nome']) > 120 || !preg_match("/^[\p{L}][\p{L}\s'.-]+$/u", $data['nome'])) {
            $errors['nome'] = 'Informe seu nome completo (somente letras).';
        } elseif (!str_contains($data['nome'], ' ')) {
            $errors['nome'] = 'Informe nome e sobrenome.';
        }
        if (!Validator::phone($data['telefone'])) {
            $errors['telefone'] = 'Telefone inválido. Use DDD + número, ex.: (18) 99123-4567.';
        }
        if (!in_array($data['genero'], generos(), true)) {
            $errors['genero'] = 'Selecione uma opção.';
        }
        if (!isset(estados()[$data['estado']])) {
            $errors['estado'] = 'Selecione o estado.';
        }
        if (mb_strlen($data['cidade']) < 2 || mb_strlen($data['cidade']) > 100) {
            $errors['cidade'] = 'Informe a cidade.';
        }

        return [$data, $errors];
    }

    /** Dados que só podem ser definidos no cadastro (e-mail, CPF, nascimento). */
    public static function identity(array $in, bool $checkEmail = true): array
    {
        $errors = [];
        $data = [
            'email'     => mb_strtolower(trim((string) ($in['email'] ?? ''))),
            'cpf'       => Validator::digits((string) ($in['cpf'] ?? '')),
            'data_nasc' => (string) ($in['data_nasc'] ?? ''),
        ];

        if ($checkEmail) {
            if (!Validator::email($data['email'])) {
                $errors['email'] = 'Informe um e-mail válido.';
            } elseif (User::emailTaken($data['email'])) {
                $errors['email'] = 'Este e-mail já está cadastrado. Tente entrar ou recuperar a senha.';
            }
        }

        if (!Validator::cpf($data['cpf'])) {
            $errors['cpf'] = 'CPF inválido.';
        } elseif (User::cpfTaken($data['cpf'])) {
            $errors['cpf'] = 'Este CPF já está cadastrado.';
        }

        if (!Validator::date($data['data_nasc'])) {
            $errors['data_nasc'] = 'Informe a data de nascimento.';
        } elseif (Validator::age($data['data_nasc']) < 18) {
            $errors['data_nasc'] = 'É preciso ter 18 anos ou mais.';
        } elseif (Validator::age($data['data_nasc']) > 120) {
            $errors['data_nasc'] = 'Data de nascimento inválida.';
        }

        return [$data, $errors];
    }

    /**
     * Horários de atendimento: horario[dia][ativo|inicio|fim], dia de 0 (domingo) a 6.
     * @return array{0: array<int, array{inicio: string, fim: string}>, 1: array}
     */
    public static function schedule(array $in): array
    {
        $schedule = [];
        $errors = [];
        foreach ((array) ($in['horario'] ?? []) as $day => $row) {
            $day = (int) $day;
            if ($day < 0 || $day > 6 || !is_array($row) || empty($row['ativo'])) {
                continue;
            }
            $start = (string) ($row['inicio'] ?? '');
            $end = (string) ($row['fim'] ?? '');
            $naDia = ($day === 0 || $day === 6 ? 'No ' : 'Na ') . mb_strtolower(Schedule::DIAS[$day]);
            $valid = static fn ($t) => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);

            if (!$valid($start) || !$valid($end)) {
                $errors['horario'] = $naDia . ', informe o horário de início e de fim.';
            } elseif ($end <= $start) {
                $errors['horario'] = $naDia . ', o horário final deve ser depois do inicial.';
            } else {
                $schedule[$day] = ['inicio' => $start, 'fim' => $end];
            }
        }
        ksort($schedule);
        return [$schedule, $errors];
    }

    /** Perfil profissional: profissão, descrição, categorias e faixa de preço. */
    public static function professional(array $in): array
    {
        $errors = [];
        $aCombinar = !empty($in['preco_combinar']);
        $min = $aCombinar ? null : Validator::money($in['preco_min'] ?? null);
        $max = $aCombinar ? null : Validator::money($in['preco_max'] ?? null);

        $profile = [
            'profissao' => preg_replace('/\s+/', ' ', trim((string) ($in['profissao'] ?? ''))),
            'descricao' => trim((string) ($in['descricao'] ?? '')),
            'preco_min' => $min,
            'preco_max' => $max,
        ];
        $categories = Category::validIds((array) ($in['categorias'] ?? []));

        if (mb_strlen($profile['profissao']) < 3 || mb_strlen($profile['profissao']) > 80) {
            $errors['profissao'] = 'Informe sua profissão (ex.: Eletricista).';
        }
        if (mb_strlen($profile['descricao']) > 1000) {
            $errors['descricao'] = 'A descrição pode ter no máximo 1000 caracteres.';
        }
        if (!$categories) {
            $errors['categorias'] = 'Escolha pelo menos uma categoria.';
        }
        if (($min !== null && ($min < 0 || $min > 99999999)) || ($max !== null && ($max < 0 || $max > 99999999))) {
            $errors['preco'] = 'Informe valores válidos.';
        } elseif ($min !== null && $max !== null && $min > $max) {
            $errors['preco'] = 'O preço mínimo não pode ser maior que o máximo.';
        }
        if ($profile['descricao'] === '') {
            $profile['descricao'] = null;
        }

        return [$profile, $categories, $errors];
    }
}
