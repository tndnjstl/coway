<!--   Core JS Files   -->
<script src="/assets/js/core/jquery-3.7.1.min.js"></script>
<script src="/assets/js/core/popper.min.js"></script>
<script src="/assets/js/core/bootstrap.min.js"></script>

<!-- jQuery Scrollbar -->
<script src="/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

<!-- Kaiadmin JS -->
<script src="/assets/js/kaiadmin.min.js"></script>

<script>
/* authFetch - AJAX 요청 시 401 로그인 처리 */
window.__authRedirecting = false;

async function authFetch(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['X-Requested-With'] = 'XMLHttpRequest';

    const res = await fetch(url, options);

    if (res.status === 401) {
        let redirectUrl = '/Auth/login?next=' + encodeURIComponent(location.pathname + location.search) + '&msg=auth';
        try {
            const payload = await res.clone().json();
            if (payload && payload.redirect) redirectUrl = payload.redirect;
        } catch (_) {}

        if (!window.__authRedirecting) {
            window.__authRedirecting = true;
            alert('로그인이 필요한 서비스입니다.\n로그인 페이지로 이동합니다.');
            location.href = redirectUrl;
        }
        throw new Error('UNAUTHORIZED');
    }

    return res;
}
</script>