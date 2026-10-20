<?php

final class PageController
{
    public function home(): void
    {
        View::render('home/index', [
            'title'      => null,
            'categories' => Category::allWithCounts(),
            'featured'   => ProviderSearch::featured(4),
            'reviews'    => Review::latest(3),
            'counts'     => User::counts(),
            'origin'     => Location::current(),
        ]);
    }

    public function about(): void
    {
        View::render('pages/about', ['title' => 'Sobre', 'counts' => User::counts()]);
    }

    public function support(): void
    {
        View::render('pages/support', ['title' => 'Suporte']);
    }

    public function privacy(): void
    {
        View::render('pages/privacy', ['title' => 'Termos e privacidade']);
    }

    public function contact(): void
    {
        View::render('pages/contact', ['title' => 'Contato']);
    }

    public function sendContact(): void
    {
        $subjects = ['duvida' => 'Dúvida', 'problema' => 'Problema', 'sugestao' => 'Sugestão', 'parceria' => 'Parceria', 'outro' => 'Outro'];
        $data = [
            'nome'     => trim((string) input('nome', '')),
            'email'    => mb_strtolower(trim((string) input('email', ''))),
            'telefone' => Validator::digits((string) input('telefone', '')) ?: null,
            'assunto'  => (string) input('assunto', ''),
            'mensagem' => trim((string) input('mensagem', '')),
        ];

        $errors = [];
        if (mb_strlen($data['nome']) < 3 || mb_strlen($data['nome']) > 120) {
            $errors['nome'] = 'Informe seu nome.';
        }
        if (!Validator::email($data['email'])) {
            $errors['email'] = 'Informe um e-mail válido.';
        }
        if ($data['telefone'] !== null && !Validator::phone($data['telefone'])) {
            $errors['telefone'] = 'Telefone inválido.';
        }
        if (!isset($subjects[$data['assunto']])) {
            $errors['assunto'] = 'Escolha um assunto.';
        }
        if (mb_strlen($data['mensagem']) < 10 || mb_strlen($data['mensagem']) > 3000) {
            $errors['mensagem'] = 'A mensagem deve ter entre 10 e 3000 caracteres.';
        }
        if (empty($_POST['privacidade'])) {
            $errors['privacidade'] = 'É preciso concordar com a política de privacidade.';
        }

        if ($errors) {
            Session::withInput($_POST, $errors);
            Session::flash('error', 'Confira os campos destacados.');
            redirect('/contato#formulario');
        }

        Database::insert('contatos', $data);

        if ($to = Config::get('contact_email')) {
            Mailer::send(
                $to,
                'Contato: ' . $subjects[$data['assunto']] . ' - ' . $data['nome'],
                '<p><strong>' . e($data['nome']) . '</strong> (' . e($data['email']) . ')</p>'
                . '<p>' . nl2br(e($data['mensagem'])) . '</p>',
                $data['email']
            );
        }

        Session::flash('success', 'Mensagem enviada! Responderemos em até 24 horas úteis.');
        redirect('/contato');
    }
}
