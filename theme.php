<script>
    (function() {
        const saved = localStorage.getItem('turbo_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
    })();
</script>

<style>
    /* ========== LIGHT THEME – UNIVERSAL FIX ========== */
    [data-theme="light"] body,
    [data-theme="light"] body * {
        color: #1a1a2e !important;
        background-color: transparent !important; /* allow container backgrounds to show */
    }

    /* Restore container backgrounds (white) */
    [data-theme="light"] .navbar,
    [data-theme="light"] .card,
    [data-theme="light"] .profile-container,
    [data-theme="light"] .edit-container,
    [data-theme="light"] .password-container,
    [data-theme="light"] .forgot-container,
    [data-theme="light"] .register-container,
    [data-theme="light"] .auth-container,
    [data-theme="light"] .settings-card,
    [data-theme="light"] .faq-card,
    [data-theme="light"] .tickets-card,
    [data-theme="light"] .contact-card,
    [data-theme="light"] .form-card,
    [data-theme="light"] .history-card,
    [data-theme="light"] .danger-card,
    [data-theme="light"] .station-card,
    [data-theme="light"] .stat-card,
    [data-theme="light"] .stat-mini,
    [data-theme="light"] .info-card,
    [data-theme="light"] .result-item,
    [data-theme="light"] .ticket-item,
    [data-theme="light"] .faq-item,
    [data-theme="light"] .main-content,
    [data-theme="light"] .container,
    [data-theme="light"] .quick-booking,
    [data-theme="light"] .profile-section,
    [data-theme="light"] body {
        background-color: #ffffff !important;
    }

    /* ===== EXCEPTIONS ===== */

    /* Keep orange accent text */
    [data-theme="light"] .logo,
    [data-theme="light"] .page-title h1 span,
    [data-theme="light"] .hero span,
    [data-theme="light"] .cost-highlight,
    [data-theme="light"] .profile-section h2,
    [data-theme="light"] .card h2,
    [data-theme="light"] .quick-booking h2,
    [data-theme="light"] .stat-value.accent,
    [data-theme="light"] .fuel-badge,
    [data-theme="light"] .price-banner {
        color: #f97316 !important;
    }

    /* Auth banner stays white text on dark */
    [data-theme="light"] .auth-banner,
    [data-theme="light"] .auth-banner * {
        color: #ffffff !important;
        background-color: initial !important;
    }

    /* Input backgrounds */
    [data-theme="light"] input,
    [data-theme="light"] select,
    [data-theme="light"] textarea {
        background-color: #f0f0f3 !important;
        color: #1a1a2e !important;
        border-color: #e0e0e6 !important;
    }

    /* Table header rows */
    [data-theme="light"] table th {
        background-color: #f0f0f3 !important;
    }

    /* Toggle slider */
    [data-theme="light"] .toggle-slider {
        background-color: #d0d0d8 !important;
    }
</style>