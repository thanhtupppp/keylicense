import "./bootstrap";
import "./admin/license-batch-created";
import "./admin/license-create";
import Alpine from "alpinejs";

window.Alpine = Alpine;

document.addEventListener("alpine:init", () => {
    Alpine.data("adminLayout", () => ({
        sidebarCollapsed: false,
        mobileSidebarOpen: false,
        readBoolean(key, fallback = false) {
            try {
                const raw = localStorage.getItem(key);
                return raw !== null ? JSON.parse(raw) : fallback;
            } catch (error) {
                return fallback;
            }
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem(
                "admin-sidebar-collapsed",
                JSON.stringify(this.sidebarCollapsed),
            );
        },
        openMobileSidebar() {
            this.mobileSidebarOpen = true;
        },
        closeMobileSidebar() {
            this.mobileSidebarOpen = false;
        },
        init() {
            this.sidebarCollapsed = this.readBoolean(
                "admin-sidebar-collapsed",
                false,
            );
        },
    }));

    Alpine.data("publicLayout", () => ({
        mobileNavOpen: false,
        theme: "light",
        readString(key, fallback = "light") {
            try {
                const raw = localStorage.getItem(key);
                return raw !== null ? raw : fallback;
            } catch (error) {
                return fallback;
            }
        },
        toggleMobileNav() {
            this.mobileNavOpen = !this.mobileNavOpen;
        },
        closeMobileNav() {
            this.mobileNavOpen = false;
        },
        toggleTheme() {
            this.theme = this.theme === "light" ? "dark" : "light";
            localStorage.setItem("public-theme", this.theme);
            document.documentElement.classList.toggle(
                "dark",
                this.theme === "dark",
            );
        },
        init() {
            this.theme = this.readString("public-theme", "light");
            document.documentElement.classList.toggle(
                "dark",
                this.theme === "dark",
            );
        },
    }));
});

Alpine.start();
