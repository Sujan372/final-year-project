<script>
    // Apply saved theme on page load
    (function() {
        const saved = localStorage.getItem('turbo_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
    })();
</script>

<style>
    [data-theme="light"] body {
        background: #f5f5f7 !important;
        background-image: none !important;
    }

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
    [data-theme="light"] .container {
        background: #ffffff !important;
        border-color: #e0e0e6 !important;
    }

    [data-theme="light"] input,
    [data-theme="light"] select,
    [data-theme="light"] textarea {
        background: #f0f0f3 !important;
        color: #1a1a2e !important;
        border-color: #e0e0e6 !important;
    }

    [data-theme="light"] input::placeholder,
    [data-theme="light"] textarea::placeholder {
        color: #a0a0b0 !important;
    }

    [data-theme="light"] table th {
        background: #f0f0f3 !important;
    }

    [data-theme="light"] table td {
        border-bottom-color: #e0e0e6 !important;
    }

    [data-theme="light"] table tbody tr:hover {
        background: #f0f0f3 !important;
    }

    [data-theme="light"] .btn-outline,
    [data-theme="light"] .cancel-btn,
    [data-theme="light"] .back-btn,
    [data-theme="light"] .home-btn {
        background: #f0f0f3 !important;
        border-color: #e0e0e6 !important;
    }

    [data-theme="light"] .toggle-slider {
        background: #d0d0d8 !important;
    }

    [data-theme="light"] .auth-banner,
    [data-theme="light"] .auth-banner * {
        color: #ffffff !important;
    }
</style>