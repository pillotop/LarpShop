{{-- Apply the saved preference before styles load to avoid a flash of the wrong theme. --}}
<script>
    (() => {
        let preference;
        try {
            preference = localStorage.getItem('larpshop.theme');
        } catch {}

        document.documentElement.dataset.bsTheme = ['light', 'dark'].includes(preference)
            ? preference
            : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    })();
</script>
