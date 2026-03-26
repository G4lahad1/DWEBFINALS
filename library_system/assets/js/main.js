document.addEventListener("DOMContentLoaded", function () {
    var sidebar  = document.getElementById("sidebar");
    var overlay  = document.getElementById("sidebarOverlay");
    var btn      = document.querySelector(".sidebarBtn");

    if (!sidebar || !btn) return;

    /* Is the viewport in mobile/tablet range? */
    function isMobile() {
        return window.innerWidth <= 1024;
    }

    /* Open the sidebar */
    function openSidebar() {
        sidebar.classList.add("active");
        btn.classList.replace("bx-menu", "bx-menu-alt-right");
        if (overlay && isMobile()) {
            overlay.classList.add("active");
            document.body.style.overflow = "hidden"; // prevent scroll-behind
        }
    }

    /* Close the sidebar */
    function closeSidebar() {
        sidebar.classList.remove("active");
        btn.classList.replace("bx-menu-alt-right", "bx-menu");
        if (overlay) {
            overlay.classList.remove("active");
        }
        document.body.style.overflow = "";
    }

    /* Hamburger button toggles open/close */
    btn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (sidebar.classList.contains("active")) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    /* Tapping the dark overlay closes the sidebar */
    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    /* On resize: if viewport grows past mobile, close & clean up */
    window.addEventListener("resize", function () {
        if (!isMobile()) {
            if (overlay) overlay.classList.remove("active");
            document.body.style.overflow = "";
            // Don't remove sidebar.active on desktop — let user keep it collapsed
        } else {
            // On mobile, if sidebar was "active" in desktop-collapse mode, close it
            if (sidebar.classList.contains("active")) {
                closeSidebar();
            }
        }
    });
});
