/* Nexus Network: interações da interface (sem dependências) */
(function () {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const BASE = document.body.dataset.base || '/';
    const CSRF = $('meta[name="csrf-token"]')?.content || '';

    window.Nexus = { url: (path) => BASE.replace(/\/$/, '') + '/' + path.replace(/^\//, ''), csrf: CSRF };

    /* ---------- Cabeçalho ---------- */
    const header = $('[data-header]');
    const onScroll = () => header?.classList.toggle('is-scrolled', window.scrollY > 8);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    const nav = $('[data-nav]');
    const navToggle = $('[data-nav-toggle]');
    navToggle?.addEventListener('click', () => {
        const open = nav.classList.toggle('is-open');
        navToggle.setAttribute('aria-expanded', String(open));
        navToggle.querySelector('i').className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        document.body.style.overflow = open ? 'hidden' : '';
    });

    /* ---------- Dropdowns ---------- */
    const closeDropdowns = (except) => $$('[data-dropdown].is-open').forEach((d) => {
        if (d !== except) {
            d.classList.remove('is-open');
            d.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
        }
    });
    $$('[data-dropdown-toggle]').forEach((btn) => btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const dd = btn.closest('[data-dropdown]');
        closeDropdowns(dd);
        const open = dd.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', String(open));
    }));
    document.addEventListener('click', (e) => { if (!e.target.closest('[data-dropdown]')) closeDropdowns(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDropdowns(); });

    /* ---------- Toasts ---------- */
    const dismiss = (toast) => {
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 300);
    };
    window.Nexus.toast = (type, message) => {
        const box = $('[data-toasts]');
        if (!box) return;
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
        const el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + '"></i><p></p>'
            + '<button type="button" class="toast-close" aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button>';
        el.querySelector('p').textContent = message;
        el.querySelector('button').addEventListener('click', () => dismiss(el));
        box.appendChild(el);
        setTimeout(() => el.isConnected && dismiss(el), 6000);
    };
    $$('.toast').forEach((t) => {
        t.querySelector('[data-toast-close]')?.addEventListener('click', () => dismiss(t));
        if (!t.classList.contains('toast-error')) setTimeout(() => t.isConnected && dismiss(t), 7000);
    });

    /* ---------- Confirmações ---------- */
    $$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    }));

    $$('button[data-confirm-click]').forEach((btn) => btn.addEventListener('click', (e) => {
        if (!window.confirm(btn.dataset.confirmClick)) e.preventDefault();
    }));

    /* ---------- Sino de notificações (contador atualizado a cada minuto) ---------- */
    const bell = $('[data-bell-count]');
    if (bell) {
        let last = parseInt(bell.textContent, 10) || 0;
        const refreshBell = async () => {
            if (document.hidden) return;
            try {
                const res = await fetch(window.Nexus.url('/api/notificacoes'),{ headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const { nao_lidas: unread } = await res.json();
                bell.textContent = unread > 9 ? '9+' : String(unread);
                bell.hidden = unread < 1;
                if (unread > last && window.Nexus?.toast) window.Nexus.toast('info', 'Você tem novas notificações.');
                last = unread;
            } catch (e) { /* tenta de novo no próximo ciclo */ }
        };
        setInterval(refreshBell, 60000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshBell(); });
    }

    /* ---------- Envio automático de filtros ---------- */
    $$('form[data-autosubmit]').forEach((form) => {
        form.addEventListener('change', (e) => {
            if (e.target.matches('select, input[type="checkbox"], input[type="radio"]')) form.submit();
        });
    });
    $$('[data-autosubmit-file]').forEach((input) => input.addEventListener('change', () => {
        if (input.files.length) input.form.submit();
    }));

    /* ---------- Painel de filtros (mobile) ---------- */
    const filters = $('[data-filters]');
    $$('[data-filters-toggle]').forEach((btn) => btn.addEventListener('click', () => {
        const open = filters.classList.toggle('is-open');
        document.body.classList.toggle('filters-open', open);
    }));

    /* ---------- Horários de atendimento (perfil do prestador) ---------- */
    const scheduleForm = $('[data-schedule-form]');
    if (scheduleForm) {
        const setRow = (row, active, start, end) => {
            const toggle = $('[data-schedule-toggle]', row);
            const [ini, fim] = $$('input[type="time"]', row);
            toggle.checked = active;
            ini.disabled = fim.disabled = !active;
            if (start) ini.value = start;
            if (end) fim.value = end;
            row.classList.toggle('is-off', !active);
        };
        $$('[data-schedule-row]', scheduleForm).forEach((row) => {
            $('[data-schedule-toggle]', row).addEventListener('change', (e) => setRow(row, e.target.checked));
        });
        const presets = {
            comercial: { 1: ['08:00', '18:00'], 2: ['08:00', '18:00'], 3: ['08:00', '18:00'], 4: ['08:00', '18:00'], 5: ['08:00', '18:00'] },
            'comercial-sab': { 1: ['08:00', '18:00'], 2: ['08:00', '18:00'], 3: ['08:00', '18:00'], 4: ['08:00', '18:00'], 5: ['08:00', '18:00'], 6: ['08:00', '12:00'] },
            limpar: {},
        };
        $$('[data-schedule-preset]', scheduleForm).forEach((btn) => btn.addEventListener('click', () => {
            const preset = presets[btn.dataset.schedulePreset];
            $$('[data-schedule-row]', scheduleForm).forEach((row) => {
                const period = preset[row.dataset.day];
                setRow(row, Boolean(period), period?.[0], period?.[1]);
            });
        }));
    }

    /* ---------- Login rápido (banco de teste) ---------- */
    $$('[data-fill-login]').forEach((btn) => btn.addEventListener('click', () => {
        const form = $('form[action$="/entrar"]');
        form.email.value = btn.dataset.fillLogin;
        form.senha.value = 'Nexus@123';
        form.submit();
    }));

    /* ---------- Senhas ---------- */
    $$('[data-toggle-password]').forEach((btn) => btn.addEventListener('click', () => {
        const input = btn.parentElement.querySelector('input');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
    }));

    $$('[data-password]').forEach((input) => {
        const form = input.form;
        const confirm = $('[data-password-confirm]', form);
        const rules = $('[data-password-rules]', form);
        if (!rules) return;
        const tests = {
            length: (v) => v.length >= 8,
            upper: (v) => /[A-Z]/.test(v),
            lower: (v) => /[a-z]/.test(v),
            number: (v) => /[0-9]/.test(v),
            symbol: (v) => /[^A-Za-z0-9]/.test(v),
            match: (v) => v.length > 0 && confirm && v === confirm.value,
        };
        const check = () => {
            Object.entries(tests).forEach(([rule, test]) => {
                const ok = test(input.value);
                $('[data-rule="' + rule + '"]', rules)?.classList.toggle('is-ok', ok);
            });
            confirm?.setCustomValidity(confirm.value && confirm.value !== input.value ? 'As senhas não coincidem.' : '');
        };
        input.addEventListener('input', check);
        confirm?.addEventListener('input', check);
    });

    /* ---------- Máscaras ---------- */
    const masks = {
        phone(v) {
            const d = v.replace(/\D/g, '').slice(0, 11);
            if (d.length <= 2) return d.length ? '(' + d : '';
            if (d.length <= 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
            if (d.length <= 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
            return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
        },
        cpf(v) {
            const d = v.replace(/\D/g, '').slice(0, 11);
            return d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        },
        money(v) {
            const d = v.replace(/\D/g, '').slice(0, 10);
            if (!d) return '';
            return (parseInt(d, 10) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        },
    };
    $$('[data-mask]').forEach((input) => {
        const fn = masks[input.dataset.mask];
        input.addEventListener('input', () => { input.value = fn(input.value); });
    });

    /* ---------- Contador de caracteres ---------- */
    $$('[data-counter]').forEach((el) => {
        const counter = document.createElement('span');
        counter.className = 'char-counter';
        const update = () => { counter.textContent = el.value.length + ' / ' + el.maxLength; };
        el.insertAdjacentElement('afterend', counter);
        el.addEventListener('input', update);
        update();
    });

    /* ---------- Cadastro: tipo de conta, foto e preço ---------- */
    const registerForm = $('[data-register-form]');
    if (registerForm) {
        const providerFields = $('[data-provider-fields]', registerForm);
        const sync = () => {
            const isProvider = $('[data-type-radio]:checked', registerForm)?.value === 'prestador';
            providerFields.hidden = !isProvider;
            $$('[data-required-prestador]', registerForm).forEach((el) => { el.required = isProvider; });
            const url = new URL(window.location.href);
            url.searchParams.set('tipo', isProvider ? 'prestador' : 'cliente');
            window.history.replaceState(null, '', url);
        };
        $$('[data-type-radio]', registerForm).forEach((r) => r.addEventListener('change', sync));
        sync();
    }

    $$('[data-photo-input]').forEach((input) => input.addEventListener('change', () => {
        const file = input.files[0];
        const preview = $('[data-photo-preview]');
        if (!file || !preview) return;
        if (file.size > 5 * 1024 * 1024) {
            window.Nexus.toast('error', 'A foto deve ter no máximo 5 MB.');
            input.value = '';
            return;
        }
        const img = document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(file);
        preview.replaceChildren(img);
    }));

    $$('[data-price-toggle]').forEach((toggle) => {
        const group = toggle.closest('[data-price-group]');
        const sync = () => $$('input[data-mask="money"]', group).forEach((i) => { i.disabled = toggle.checked; });
        toggle.addEventListener('change', sync);
        sync();
    });

    /* ---------- Estados e cidades (API do IBGE) + coordenadas (Nominatim) ---------- */
    const geocodeCity = async (city, uf) => {
        const params = new URLSearchParams({ city, state: uf, country: 'Brasil', format: 'json', limit: '1' });
        const res = await fetch('https://nominatim.openstreetmap.org/search?' + params, { headers: { 'Accept-Language': 'pt-BR' } });
        const data = await res.json();
        return data[0] ? { lat: data[0].lat, lon: data[0].lon } : null;
    };

    $$('[data-uf-select]').forEach((select) => {
        const form = select.form;
        const cityInput = $('[data-city-input]', form);
        const list = $('[data-city-list]', form);
        const lat = $('[data-lat]', form);
        const lon = $('[data-lon]', form);
        const cache = {};

        const loadCities = async () => {
            const uf = select.value;
            list.replaceChildren();
            if (!uf) return;
            try {
                cache[uf] = cache[uf] || await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + uf + '/municipios?orderBy=nome')
                    .then((r) => r.json()).then((rows) => rows.map((m) => m.nome));
                cache[uf].forEach((name) => {
                    const opt = document.createElement('option');
                    opt.value = name;
                    list.appendChild(opt);
                });
            } catch (e) { /* sem a lista, o campo continua aceitando texto livre */ }
        };

        const locate = async () => {
            lat.value = '';
            lon.value = '';
            if (!cityInput.value.trim() || !select.value) return;
            try {
                const coords = await geocodeCity(cityInput.value.trim(), select.value);
                if (coords) { lat.value = coords.lat; lon.value = coords.lon; }
            } catch (e) { /* o servidor tenta novamente ao salvar */ }
        };

        select.addEventListener('change', () => { loadCities(); locate(); });
        cityInput.addEventListener('change', locate);
        if (select.value) loadCities();
    });

    /* ---------- Localização na busca ---------- */
    const reverseGeocode = async (lat, lon) => {
        try {
            const params = new URLSearchParams({ lat, lon, format: 'json', zoom: '10', addressdetails: '1' });
            const data = await fetch('https://nominatim.openstreetmap.org/reverse?' + params, { headers: { 'Accept-Language': 'pt-BR' } }).then((r) => r.json());
            const a = data.address || {};
            const city = a.city || a.town || a.village || a.municipality;
            const uf = (a['ISO3166-2-lvl4'] || '').replace('BR-', '');
            return city ? city + (uf ? ', ' + uf : '') : 'Minha localização';
        } catch (e) {
            return 'Minha localização';
        }
    };

    const saveLocation = (data) => fetch(window.Nexus.url('localizacao'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF },
        body: new URLSearchParams(data),
    });

    $$('[data-location-form]').forEach((box) => {
        const input = $('[data-location-input]', box);
        const lat = $('[data-lat]', box);
        const lon = $('[data-lon]', box);
        const locateBtn = $('[data-locate]', box);
        const applyBtn = $('[data-location-apply]', box);
        const isHomeForm = box.tagName === 'FORM';

        input?.addEventListener('input', () => { lat.value = ''; lon.value = ''; });

        locateBtn?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                window.Nexus.toast('error', 'Seu navegador não permite obter a localização.');
                return;
            }
            locateBtn.classList.add('is-loading');
            navigator.geolocation.getCurrentPosition(async (pos) => {
                const { latitude, longitude } = pos.coords;
                const label = await reverseGeocode(latitude, longitude);
                locateBtn.classList.remove('is-loading');
                if (isHomeForm) {
                    input.value = label;
                    lat.value = latitude;
                    lon.value = longitude;
                } else {
                    await saveLocation({ lat: latitude, lon: longitude, label });
                    window.location.reload();
                }
            }, () => {
                locateBtn.classList.remove('is-loading');
                window.Nexus.toast('error', 'Não foi possível obter sua localização. Digite sua cidade.');
            }, { timeout: 10000, maximumAge: 600000 });
        });

        const parseCity = (text) => {
            const m = text.match(/^\s*(.+?)\s*[,\-/]\s*([A-Za-z]{2})\s*$/);
            return m ? { city: m[1], uf: m[2].toUpperCase() } : null;
        };

        const geocodeInput = async () => {
            const parsed = parseCity(input.value);
            if (!parsed || lat.value) return;
            try {
                const coords = await Promise.race([
                    geocodeCity(parsed.city, parsed.uf),
                    new Promise((resolve) => setTimeout(() => resolve(null), 4000)),
                ]);
                if (coords) { lat.value = coords.lat; lon.value = coords.lon; }
            } catch (e) { /* o servidor tenta pelo cache */ }
        };

        if (isHomeForm) {
            box.addEventListener('submit', async (e) => {
                if (!input.value.trim() || lat.value) return;
                e.preventDefault();
                await geocodeInput();
                box.submit();
            });
        }

        applyBtn?.addEventListener('click', async () => {
            if (!input.value.trim()) { input.focus(); return; }
            if (!parseCity(input.value)) {
                window.Nexus.toast('info', 'Digite no formato "Cidade, UF". Ex.: Presidente Prudente, SP');
                return;
            }
            applyBtn.disabled = true;
            await geocodeInput();
            const url = new URL(window.location.href);
            url.searchParams.set('local', input.value.trim());
            url.searchParams.delete('pagina');
            if (lat.value) { url.searchParams.set('lat', lat.value); url.searchParams.set('lon', lon.value); }
            window.location.href = url;
        });
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && applyBtn) { e.preventDefault(); applyBtn.click(); }
        });
    });

    // Remove lat/lon/local da barra de endereço depois de aplicados
    (function cleanLocationParams() {
        const url = new URL(window.location.href);
        if (url.searchParams.has('local')) {
            ['local', 'lat', 'lon'].forEach((p) => url.searchParams.delete(p));
            window.history.replaceState(null, '', url);
        }
    })();

    /* ---------- FAQ ---------- */
    const faqSearch = $('[data-faq-search]');
    if (faqSearch) {
        const items = $$('[data-faq-group]');
        const empty = $('[data-faq-empty]');
        let group = 'todos';
        const normalize = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        const apply = () => {
            const term = normalize(faqSearch.value.trim());
            let visible = 0;
            items.forEach((item) => {
                const show = (group === 'todos' || item.dataset.faqGroup === group) && (!term || normalize(item.textContent).includes(term));
                item.hidden = !show;
                if (show) visible++;
                if (term && show) item.open = true;
            });
            empty.hidden = visible > 0;
        };
        $$('[data-faq-tab]').forEach((tab) => tab.addEventListener('click', () => {
            $$('[data-faq-tab]').forEach((t) => { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
            tab.classList.add('is-active');
            tab.setAttribute('aria-selected', 'true');
            group = tab.dataset.faqTab;
            apply();
        }));
        faqSearch.addEventListener('input', apply);
        const hash = window.location.hash.slice(1);
        if (hash) $('[data-faq-tab="' + hash + '"]')?.click();
    }

    /* ---------- Navegação ativa no perfil ---------- */
    const profileLinks = $$('.profile-nav a');
    if (profileLinks.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) {
                profileLinks.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === '#' + entry.target.id));
            }
        }), { rootMargin: '-40% 0px -55% 0px' });
        profileLinks.forEach((a) => { const s = $(a.getAttribute('href')); if (s) observer.observe(s); });
    }

    /* ---------- Leitura em voz alta ---------- */
    const ttsBtn = $('[data-tts-toggle]');
    if (ttsBtn && 'speechSynthesis' in window) {
        let enabled = false;
        try { enabled = localStorage.getItem('nexus-tts') === '1'; } catch (e) { /* armazenamento indisponível */ }
        let current = null;
        let timer = null;
        const readable = 'h1, h2, h3, p, li, a, button, label, blockquote, summary, .stat';

        const speak = (el) => {
            const text = (el.getAttribute('aria-label') || el.innerText || '').trim();
            if (!text || el === current) return;
            current?.classList.remove('tts-reading');
            current = el;
            el.classList.add('tts-reading');
            window.speechSynthesis.cancel();
            const u = new SpeechSynthesisUtterance(text.slice(0, 400));
            u.lang = 'pt-BR';
            u.onend = () => el.classList.remove('tts-reading');
            window.speechSynthesis.speak(u);
        };
        const onPoint = (e) => {
            const el = e.target.closest(readable);
            if (!el || el === ttsBtn) return;
            clearTimeout(timer);
            timer = setTimeout(() => speak(el), 250);
        };
        const setState = (on) => {
            enabled = on;
            ttsBtn.setAttribute('aria-pressed', String(on));
            try { localStorage.setItem('nexus-tts', on ? '1' : '0'); } catch (e) { /* ignora */ }
            if (!on) {
                window.speechSynthesis.cancel();
                current?.classList.remove('tts-reading');
                current = null;
            }
        };
        document.addEventListener('mouseover', (e) => enabled && onPoint(e));
        document.addEventListener('focusin', (e) => enabled && onPoint(e));
        ttsBtn.addEventListener('click', () => {
            setState(!enabled);
            const u = new SpeechSynthesisUtterance(enabled ? 'Leitura em voz alta ativada' : 'Leitura em voz alta desativada');
            u.lang = 'pt-BR';
            window.speechSynthesis.speak(u);
        });
        setState(enabled);
    } else if (ttsBtn) {
        ttsBtn.hidden = true;
    }
})();
