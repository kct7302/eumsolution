(() => {
    const form = document.querySelector('.consult-form');
    if (!form) return;

    const token = form.elements.namedItem('csrf_token');
    const submit = form.querySelector('[type="submit"]');
    const status = document.querySelector('#form-status');
    const retry = document.querySelector('#form-retry');
    const fields = ['company', 'name', 'phone', 'email', 'message', 'inquiry_type'];

    function showMessage(type, message) {
        status.className = `alert ${type === 'success' ? 'success' : 'error'}`;
        // Messages and restored input are data, never HTML.
        status.textContent = message;
        status.hidden = false;
    }

    async function loadState() {
        submit.disabled = true;
        submit.textContent = '상담 양식 준비 중…';
        retry.hidden = true;
        token.value = '';
        try {
            const response = await fetch('contact.php?action=form-state', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Form state request failed');
            const state = await response.json();
            if (typeof state.csrf_token !== 'string' || !/^[a-f0-9]{64}$/.test(state.csrf_token)) {
                throw new Error('Missing form token');
            }

            token.value = state.csrf_token;
            for (const field of fields) {
                if (typeof state.old?.[field] === 'string') {
                    form.elements.namedItem(field).value = state.old[field];
                }
            }
            status.hidden = true;
            if (state.flash && typeof state.flash.message === 'string') {
                if (state.flash.type === 'success') {
                    form.reset();
                    token.value = state.csrf_token;
                }
                showMessage(state.flash.type, state.flash.message);
                status.scrollIntoView({ block: 'center' });
            }
            submit.disabled = false;
            submit.textContent = '상담 신청 보내기';
        } catch {
            showMessage('error', '상담 양식을 불러오지 못했습니다. 다시 연결해 주세요.');
            submit.textContent = '연결 확인 필요';
            retry.hidden = false;
        }
    }

    retry.addEventListener('click', loadState);
    form.addEventListener('submit', (event) => {
        if (!token.value || submit.disabled) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.textContent = '접수 중…';
    });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) loadState();
    });
    loadState();
})();
