(function ($) {
    function setResult(result, message, type) {
        if (!result) return;
        result.textContent = message || '';
        result.classList.remove('is-success', 'is-error');
        if (type) {
            result.classList.add(type === 'success' ? 'is-success' : 'is-error');
        }
    }

    function setLoading(button, isLoading, label) {
        if (!button) return;
        if (isLoading) {
            button.dataset.originalText = button.textContent;
            button.textContent = label;
            button.disabled = true;
            return;
        }
        button.textContent = button.dataset.originalText || button.textContent;
        button.disabled = false;
    }

    function openModal(modal) {
        if (!modal) return;
        modal.hidden = false;
        const result = modal.querySelector('.aioml-inline-result');
        setResult(result, '', null);
        const firstInput = modal.querySelector('input[name="access_key"]');
        if (firstInput) firstInput.focus();
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        const result = modal.querySelector('.aioml-inline-result');
        setResult(result, '', null);
    }

    function buildPayload(form, action) {
        const data = new FormData(form);
        data.append('action', action);
        data.append('nonce', aiomlCdnData.nonce);
        return data;
    }

    function postAjax(form, action, button, loadingLabel) {
        setLoading(button, true, loadingLabel);

        return $.ajax({
            url: aiomlCdnData.ajaxUrl,
            method: 'POST',
            data: buildPayload(form, action),
            processData: false,
            contentType: false,
        }).always(function () {
            setLoading(button, false);
        });
    }

    function markActive(provider) {
        const r2Badge = document.getElementById('aioml-r2-status');
        const s3Badge = document.getElementById('aioml-s3-status');

        [
            ['r2', r2Badge],
            ['s3', s3Badge],
        ].forEach(([name, badge]) => {
            if (!badge) return;
            const isActive = name === provider;
            if (isActive) {
                badge.dataset.configured = '1';
            }
            const isConfigured = badge.dataset.configured === '1';
            badge.textContent = isActive ? 'Active' : (isConfigured ? 'Configured' : 'Not Configured');
            badge.classList.toggle('is-active', isActive);
            badge.classList.toggle('is-muted', !isActive);
        });
    }

    function setupProvider(provider) {
        const modal = document.getElementById(`aioml-${provider}-modal`);
        const form = document.getElementById(`aioml-${provider}-form`);
        const openButton = document.getElementById(`aioml-open-${provider}-modal`);
        const testButton = document.getElementById(`aioml-test-${provider}`);
        const saveButton = document.getElementById(`aioml-save-${provider}`);
        const result = document.getElementById(`aioml-${provider}-result`);

        if (openButton) {
            openButton.addEventListener('click', () => openModal(modal));
        }

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        }

        if (testButton && form) {
            testButton.addEventListener('click', () => {
                if (!form.reportValidity()) return;
                setResult(result, '', null);

                postAjax(form, `aioml_test_${provider}_connection`, testButton, 'Testing...')
                    .done((response) => {
                        if (response && response.success) {
                            setResult(result, response.data.message || 'Connection successful.', 'success');
                            return;
                        }
                        setResult(result, response?.data?.message || 'Connection failed.', 'error');
                    })
                    .fail((xhr) => {
                        setResult(result, xhr.responseJSON?.data?.message || 'Connection failed.', 'error');
                    });
            });
        }

        if (form) {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!form.reportValidity()) return;
                setResult(result, '', null);

                postAjax(form, `aioml_save_${provider}_credentials`, saveButton, 'Saving...')
                    .done((response) => {
                        if (response && response.success) {
                            markActive(provider);
                            closeModal(modal);
                            return;
                        }
                        setResult(result, response?.data?.message || 'Unable to save provider.', 'error');
                    })
                    .fail((xhr) => {
                        setResult(result, xhr.responseJSON?.data?.message || 'Unable to save provider.', 'error');
                    });
            });
        }
    }

    document.querySelectorAll('.aioml-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            document.querySelectorAll('.aioml-tab').forEach((item) => {
                item.classList.toggle('is-active', item === tab);
            });
            document.querySelectorAll('.aioml-tab-panel').forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.panel === target);
            });
        });
    });

    document.querySelectorAll('.aioml-modal-close').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('.aioml-modal-backdrop'));
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.aioml-modal-backdrop:not([hidden])').forEach(closeModal);
    });

    document.querySelectorAll('.aioml-toggle-secret').forEach((button) => {
        button.addEventListener('click', () => {
            const wrapper = button.closest('.aioml-password-field');
            const input = wrapper ? wrapper.querySelector('input[name="secret_key"]') : null;
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
        });
    });

    setupProvider('r2');
    setupProvider('s3');
})(jQuery);
