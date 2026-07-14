import apiFetch from '@wordpress/api-fetch';

export function patchMediaDeleteUI() {
    /* eslint-disable */
    if (window.__folderDeletePatched) return;

    function getCurrentFolderId() {
        const storedFolder = localStorage.getItem('selectedFolder') || sessionStorage.getItem('selectedFolder');
        const folderId = parseInt(storedFolder, 10);
        if (!folderId || folderId === -1 || folderId === -2 || folderId === -3 || Number.isNaN(folderId)) return null;
        return folderId;
    }

    function getSelectionIds() {
        const frame = wp?.media?.frame;
        const selection = frame?.state?.().get?.('selection');
        if (!selection || !selection.models) return [];
        return selection.models.map(m => m.id).filter(Boolean);
    }

    function findBulkDeleteButton(root) {
        const selectors = [
            '.delete-selected',
            '.delete-permanently',
            '.delete-attachment',
        ];
        for (const sel of selectors) {
            const el = root.querySelector(sel);
            if (el) return el;
        }
        return null;
    }

    function findBulkCancelButton(root) {
        const toggles = Array.from(root.querySelectorAll('.select-mode-toggle, .media-button-select'));
        return toggles.find((btn) => {
            const text = (btn.textContent || '').toLowerCase();
            const label = (btn.getAttribute('aria-label') || '').toLowerCase();
            const title = (btn.getAttribute('title') || '').toLowerCase();
            const value = (btn.value || '').toLowerCase();
            return text.includes('cancel') || label.includes('cancel') || title.includes('cancel') || value.includes('cancel');
        }) || null;
    }

    function removeBulkButton() {
        document.querySelectorAll('.remove-from-folder-bulk-btn').forEach(b => b.remove());
    }

    function injectBulkRemoveButton() {
        const frameRoot = document.querySelector('.media-frame') || document;
        const cancelBtn = findBulkCancelButton(frameRoot);

        const deleteBtn = findBulkDeleteButton(frameRoot);
        if (!deleteBtn) {
            removeBulkButton();
            return;
        }

        const toolbar = deleteBtn.closest('.media-toolbar-secondary, .media-toolbar-primary, .media-toolbar, .media-frame-toolbar') || deleteBtn.parentElement;
        if (!toolbar) return;
        if (toolbar.querySelector('.remove-from-folder-bulk-btn')) return;

        const folderId = getCurrentFolderId();

        const btn = document.createElement('button');
        btn.className = 'button remove-from-folder-bulk-btn';
        btn.innerText = 'Remove';
        btn.style.marginLeft = '6px';

        btn.onclick = async () => {
            const currentFolderId = getCurrentFolderId();
            if (!currentFolderId) {
                alert('Please select a folder first');
                return;
            }

            const ids = getSelectionIds();
            if (!ids.length) return;

            if (!confirm(`Remove ${ids.length} file(s) from this folder?`)) return;

            btn.disabled = true;
            try {
                await Promise.all(
                    ids.map(mediaId =>
                        apiFetch({
                            url: react_data.remove_from_folder_url,
                            method: 'POST',
                            headers: {
                                'X-WP-Nonce': react_data.nonce_safedel,
                            },
                            data: {
                                media_id: mediaId,
                                term_id: currentFolderId,
                            },
                        })
                    )
                );

                const frame = wp?.media?.frame;
                const library = frame?.state?.().get?.('library');
                if (library) {
                    ids.forEach(id => {
                        const model = library.get?.(id);
                        if (model) library.remove(model);
                    });
                }

                const selection = frame?.state?.().get?.('selection');
                selection?.reset?.();
                frame?.trigger?.('selection:toggle');
            } catch (err) {
                console.error(err);
            } finally {
                btn.disabled = false;
            }
        };

        // Update button state based on folder selection
        btn.disabled = !folderId;
        btn.title = folderId ? 'Remove from folder' : 'Select a folder first';

        // Place between Delete and Cancel
        if (cancelBtn && toolbar.contains(cancelBtn)) {
            // Both buttons in same toolbar - insert before cancel
            cancelBtn.parentElement.insertBefore(btn, cancelBtn);
        } else {
            // Cancel not found or in different toolbar - insert after delete
            deleteBtn.parentElement.insertBefore(btn, deleteBtn.nextSibling);
        }
    }

    function tryPatchDetails() {
        if (window.__folderDeleteDetailsPatched) return true;

        const AttachmentDetails = window.wp?.media?.view?.Attachment?.Details?.TwoColumn;
        if (!AttachmentDetails) return false;

        const originalRender = AttachmentDetails.prototype.render;

        AttachmentDetails.prototype.render = function () {
        originalRender.apply(this, arguments);

        const model = this.model;
        const folderId = getCurrentFolderId();

        // Only show inside a folder
        if (!folderId) return this;

        // Prevent duplicates
        if (this.$el.find('.remove-from-folder-btn').length) return this;

        const separator = document.createElement('span');
        separator.className = 'links-separator';
        separator.innerText = ' | ';

        const btn = document.createElement('button');
        btn.className = 'button-link remove-from-folder-btn';
        btn.innerText = 'Remove';
        btn.style.color = '#d63638';
        btn.style.textDecoration = 'none';

        btn.onclick = () => {
            if (!confirm('Remove this file from the folder?')) return;

            apiFetch({
                url: react_data.remove_from_folder_url, // ✅ correct
                method: 'POST',
                headers: {
                    'X-WP-Nonce': react_data.nonce_safedel, // ✅ REST nonce
                },
                data: {
                    media_id: model.id,
                    term_id: folderId,
                },
            })
                .then(() => {
                    const library = wp.media.frame.state().get('library');
                    library.remove(model); // UI only
                    wp.media.frame.trigger('selection:toggle');
                })
                .catch(console.error);
        };

        const deleteBtn = this.el.querySelector('.delete-attachment');
        if (deleteBtn) {
            deleteBtn.after(separator);
            separator.after(btn);
        } else {
            this.el.appendChild(separator);
            this.el.appendChild(btn);
        }

        return this;
        };

        window.__folderDeleteDetailsPatched = true;
        return true;
    }

    window.__folderDeletePatched = true;
    tryPatchDetails();
    setInterval(() => {
        injectBulkRemoveButton();
        tryPatchDetails();
    }, 800);
    /* eslint-enable */
}
