(function ($) {

    /**
     * Remove AIOML Sidebar and reset styles
     */
    function removeAiomlDiv() {
        // 1. Remove the Sidebar Element (Global Singleton for React App)
        const root = document.getElementById('my-react-root');
        if (root) root.remove();

        // 2. Remove the Active Class from ALL frames
        document.querySelectorAll('.media-frame').forEach(frame => {
            frame.classList.remove('aioml-active');
            frame.style.removeProperty('--aioml-sidebar-width');
        });

        // 3. Reset positioning on valid wrappers
        const wrappers = document.querySelectorAll('.media-frame-content .attachments-browser, .media-frame-content .attachments-wrapper');
        wrappers.forEach(wrapper => {
            wrapper.style.position = '';
        });

        // Trigger resize to fix any grid glitches
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 10);
    }

    /**
     * Create and Inject AIOML Sidebar into a specific frame
     */
    function createAiomlDiv(targetFrame) {
        if (!targetFrame) return;

        // Try to find the browser container IN THIS FRAME
        let wrapper = targetFrame.querySelector('.media-frame-content .attachments-browser');

        // Fallback
        if (!wrapper) {
            wrapper = targetFrame.querySelector('.media-frame-content .attachments-wrapper');
        }

        if (!wrapper) {
            // If wrapper isn't ready, we might need to retry? 
            // For now, rely on caller timing.
            return;
        }

        // Add Active Class for CSS hooks
        targetFrame.classList.add('aioml-active');
        applySidebarWidth(targetFrame);

        // Ensure parent is relative for absolute children
        wrapper.style.position = 'relative';

        // Sidebar styling - Absolute positioning handled by CSS
        const aiomlDiv = document.createElement('div');
        aiomlDiv.id = 'my-react-root'; // Must remain ID for React App mounting
        aiomlDiv.className = 'aioml_sidebar';

        wrapper.appendChild(aiomlDiv);

        // Force Grid Recalculation
        requestAnimationFrame(() => {
            window.dispatchEvent(new Event('resize'));
        });
    }

    /**
     * Compute and apply sidebar width per active frame
     */
    function applySidebarWidth(frame) {
        if (!frame) return;
        const content = frame.querySelector('.media-frame-content') || frame;
        const baseWidth = content.clientWidth || frame.clientWidth || 0;
        const computed = Math.round(Math.max(220, Math.min(320, baseWidth * 0.25)));
        frame.style.setProperty('--aioml-sidebar-width', `${computed}px`);
    }

    /**
     * Set active state for tab buttons within a specific router
     */
    function setActive(activeBtn, router) {
        if (!router) return;
        router.querySelectorAll('.media-menu-item').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });

        activeBtn.classList.add('active');
        activeBtn.setAttribute('aria-selected', 'true');
    }

    // Function to ensure button exists in all visible/relevant routers
    const ensureButton = () => {
        // Try both standard router class
        const routers = document.querySelectorAll('.media-frame-router .media-router');

        routers.forEach(router => {
            // Check if we already processed this router
            if (router.querySelector('.aioml-tab-btn')) return;

            // Create Button
            const aiomlBtn = document.createElement('button');
            aiomlBtn.className = 'media-menu-item aioml-tab-btn'; // Class based
            aiomlBtn.style.order = "99"; // Ensure it's effectively last if flexbox is used without order, though appendChild works too
            aiomlBtn.textContent = 'AIOML';

            router.appendChild(aiomlBtn);

            // Button Click Handler
            aiomlBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent bubbling if needed

                const frame = aiomlBtn.closest('.media-frame');
                const localRouter = aiomlBtn.closest('.media-router');

                // Switch to "Browse" mode (Grid View) if needed
                // We attempt to find the standard 'Media Library' button to trigger its logic
                // usually has ID 'menu-item-browse' or related text.
                // In Site Icon view, headers might differ.
                let browseBtn = frame.querySelector('#menu-item-browse');

                // Fallback: look for button with specific text if ID fails
                if (!browseBtn) {
                    const buttons = Array.from(frame.querySelectorAll('.media-menu-item'));
                    browseBtn = buttons.find(b => b.textContent && b.textContent.includes('Media Library'));
                }

                if (browseBtn) {
                    browseBtn.click();
                }

                // Wait for view switch then inject sidebar
                setTimeout(() => {
                    setActive(aiomlBtn, localRouter);
                    removeAiomlDiv(); // Cleanup global ID
                    createAiomlDiv(frame); // Inject into this frame
                }, 50);
            });

            // Detect clicks on OTHER tabs in this router to cleanup
            router.addEventListener('click', (e) => {
                const target = e.target;
                if (target.classList.contains('media-menu-item') && !target.classList.contains('aioml-tab-btn')) {
                    removeAiomlDiv();
                }
            });
        });
    };

    // Hook into WP Media Modal Open event
    if (wp && wp.media && wp.media.view && wp.media.view.Modal) {
        wp.media.view.Modal.prototype.on('open', function () {
            // When ANY modal opens, check for buttons
            setTimeout(ensureButton, 100);
            setTimeout(ensureButton, 500); // Retry for laggy rendering
        });
    }

    // Polling fallback to catch frames that might not trigger standard events or exist initially
    setInterval(ensureButton, 1000);

    // Initial run
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(ensureButton, 500);
    });

    // Keep sidebar width in sync on resize
    window.addEventListener('resize', () => {
        document.querySelectorAll('.media-frame.aioml-active').forEach(applySidebarWidth);
    });

})(jQuery); 
