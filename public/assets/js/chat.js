/* Nexus Network: chat com atualização automática (polling) */
(function () {
    'use strict';

    const box = document.querySelector('[data-chat-messages]');
    const form = document.querySelector('[data-chat-form]');
    if (!box || !form) return;

    const input = form.querySelector('[data-chat-input]');
    const endpoint = box.dataset.endpoint;
    const POLL_MS = 3000;
    let lastId = parseInt(box.dataset.lastId, 10) || 0;
    let lastDay = Array.from(box.querySelectorAll('[data-day]')).pop()?.dataset.day || null;
    let polling = false;
    let sending = false;

    const nearBottom = () => box.scrollHeight - box.scrollTop - box.clientHeight < 120;
    const scrollDown = () => { box.scrollTop = box.scrollHeight; };

    const formatDay = (iso) => {
        const [y, m, d] = iso.split('-');
        const today = new Date();
        const key = (dt) => dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
        if (iso === key(today)) return 'Hoje';
        today.setDate(today.getDate() - 1);
        if (iso === key(today)) return 'Ontem';
        return d + '/' + m + '/' + y;
    };

    const render = (msg) => {
        if (box.querySelector('[data-id="' + msg.id + '"]')) return;
        box.querySelector('[data-chat-empty]')?.remove();

        if (msg.data !== lastDay) {
            lastDay = msg.data;
            const day = document.createElement('div');
            day.className = 'chat-day';
            day.dataset.day = msg.data;
            day.innerHTML = '<span></span>';
            day.firstChild.textContent = formatDay(msg.data);
            box.appendChild(day);
        }

        const el = document.createElement('div');
        el.className = 'msg ' + (msg.minha ? 'msg-out' : 'msg-in');
        el.dataset.id = msg.id;
        const p = document.createElement('p');
        p.textContent = msg.conteudo;
        const time = document.createElement('span');
        time.className = 'msg-time';
        time.textContent = msg.hora;
        if (msg.minha) {
            const check = document.createElement('i');
            check.className = 'fa-solid ' + (msg.lida ? 'fa-check-double' : 'fa-check');
            time.append(' ', check);
        }
        el.append(p, time);
        box.appendChild(el);
        lastId = Math.max(lastId, msg.id);
    };

    const poll = async () => {
        if (polling || document.hidden) return;
        polling = true;
        try {
            const res = await fetch(endpoint + '?depois=' + lastId, { headers: { Accept: 'application/json' } });
            if (res.status === 401) { window.location.reload(); return; }
            const data = await res.json();
            if (data.mensagens?.length) {
                const stick = nearBottom();
                data.mensagens.forEach(render);
                if (stick) scrollDown();
            }
        } catch (e) { /* tenta de novo no próximo ciclo */ }
        polling = false;
    };

    const send = async () => {
        const text = input.value.trim();
        if (!text || sending) return;
        sending = true;
        input.value = '';
        autosize();

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.Nexus.csrf, Accept: 'application/json' },
                body: new URLSearchParams({ conteudo: text }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.erro || 'Falha ao enviar.');
            render(data.mensagem);
            scrollDown();
        } catch (e) {
            input.value = text;
            autosize();
            window.Nexus.toast('error', e.message || 'Não foi possível enviar a mensagem.');
        }
        sending = false;
        input.focus();
    };

    const autosize = () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    };

    form.addEventListener('submit', (e) => { e.preventDefault(); send(); });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    input.addEventListener('input', autosize);

    scrollDown();
    input.focus();
    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
