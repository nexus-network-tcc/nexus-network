<?php

final class GalleryController
{
    public function store(): void
    {
        $providerId = (int) Auth::id();

        if (Gallery::count($providerId) >= Gallery::MAX_PHOTOS) {
            Session::flash('error', 'Sua galeria já tem ' . Gallery::MAX_PHOTOS . ' fotos. Exclua alguma para adicionar outra.');
            redirect('/perfil#galeria');
        }

        $caption = trim((string) input('legenda', ''));
        if (mb_strlen($caption) > 120) {
            Session::flash('error', 'A legenda pode ter no máximo 120 caracteres.');
            redirect('/perfil#galeria');
        }

        try {
            $path = Upload::photo('foto_galeria');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            redirect('/perfil#galeria');
        }
        if ($path === null) {
            Session::flash('error', 'Escolha uma imagem.');
            redirect('/perfil#galeria');
        }

        Gallery::add($providerId, $path, $caption ?: null);
        Session::flash('success', 'Foto adicionada à galeria.');
        redirect('/perfil#galeria');
    }

    public function destroy(int $id): void
    {
        $photo = Gallery::find($id);
        if (!$photo || (int) $photo['prestador_id'] !== (int) Auth::id()) {
            abort(403, 'Você só pode excluir as suas próprias fotos.');
        }

        Gallery::delete($id);
        Upload::delete($photo['caminho']);
        Session::flash('success', 'Foto removida da galeria.');
        redirect('/perfil#galeria');
    }
}
