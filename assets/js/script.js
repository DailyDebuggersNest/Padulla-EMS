document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.getElementById('wrapper');
    var menuToggle = document.getElementById('menu-toggle');
    var menuToggleIcon = document.getElementById('menu-toggle-icon');
    var desktopBreakpoint = 992;
    var sidebarStateKey = 'ems_sidebar_desktop_state';
    var defaultDesktopState = 'collapsed';

    function isDesktop() {
        return window.innerWidth >= desktopBreakpoint;
    }

    function getSavedDesktopState() {
        try {
            return localStorage.getItem(sidebarStateKey);
        } catch (e) {
            return null;
        }
    }

    function setSavedDesktopState(state) {
        try {
            localStorage.setItem(sidebarStateKey, state);
        } catch (e) {
            // Ignore storage errors and keep UI working.
        }
    }

    function applyDesktopSidebarState() {
        if (!wrapper || !isDesktop()) {
            return;
        }

        var saved = getSavedDesktopState();
        var effectiveState = saved || defaultDesktopState;

        if (effectiveState === 'expanded') {
            wrapper.classList.remove('toggled');
        } else {
            wrapper.classList.add('toggled');
        }
    }

    applyDesktopSidebarState();

    function syncToggleLabel() {
        if (!menuToggle || !wrapper || !menuToggleIcon) {
            return;
        }

        if (wrapper.classList.contains('toggled')) {
            menuToggleIcon.classList.remove('fa-chevron-left');
            menuToggleIcon.classList.add('fa-chevron-right');
            menuToggle.setAttribute('aria-label', 'Expand Sidebar');
        } else {
            menuToggleIcon.classList.remove('fa-chevron-right');
            menuToggleIcon.classList.add('fa-chevron-left');
            menuToggle.setAttribute('aria-label', 'Collapse Sidebar');
        }
    }

    if (menuToggle && wrapper) {
        menuToggle.addEventListener('click', function (event) {
            event.preventDefault();
            wrapper.classList.toggle('toggled');

            if (isDesktop()) {
                setSavedDesktopState(wrapper.classList.contains('toggled') ? 'collapsed' : 'expanded');
            }

            syncToggleLabel();
        });

        syncToggleLabel();
    }

    // Close the sidebar after selecting a menu item on mobile for a cleaner UX.
    var navLinks = document.querySelectorAll('.ua-nav-link');
    if (navLinks.length > 0 && wrapper) {
        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    wrapper.classList.remove('toggled');
                    syncToggleLabel();
                }
            });
        });
    }

    if (wrapper) {
        wrapper.classList.add('sidebar-ready');
    }

    window.addEventListener('resize', function () {
        if (isDesktop()) {
            applyDesktopSidebarState();
            syncToggleLabel();
        }
    });
});
