document.addEventListener('DOMContentLoaded', function () {
    // Delete confirmations
    document.querySelectorAll('a.btn-danger, button.btn-danger').forEach(function (button) {
        button.addEventListener('click', function (event) {
            const shouldDelete = window.confirm('Are you sure you want to delete this record?');
            if (!shouldDelete) {
                event.preventDefault();
            }
        });
    });

    // Ripple effect on buttons
    document.querySelectorAll('.btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 650);
        });
    });

    // Dark mode toggle
    const toggle = document.querySelector('.theme-toggle');
    function applyThemeIcon() {
        if (!toggle) return;
        const dark = document.documentElement.classList.contains('dark');
        const icon = toggle.querySelector('i');
        if (icon) {
            icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
        toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
    }
    applyThemeIcon();
    if (toggle) {
        toggle.addEventListener('click', function () {
            const dark = document.documentElement.classList.toggle('dark');
            document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            try { localStorage.setItem('priest-theme', dark ? 'dark' : 'light'); } catch (e) {}
            applyThemeIcon();
        });
    }

    // Animated counters for stat cards
    document.querySelectorAll('.stat-card strong').forEach(function (el) {
        const raw = el.textContent.trim().replace(/,/g, '');
        const target = parseFloat(raw);
        if (!isFinite(target) || target === 0) return;
        if (/\./.test(raw)) return;
        const duration = 900;
        const start = performance.now();
        function step(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString();
        }
        el.textContent = '0';
        requestAnimationFrame(step);
    });

    // Reveal on scroll for cards
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        document.querySelectorAll('.card, .stat-card').forEach(function (el) {
            el.classList.add('reveal');
            io.observe(el);
        });
    }
});
