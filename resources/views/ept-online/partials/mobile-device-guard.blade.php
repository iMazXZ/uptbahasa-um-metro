@push('styles')
<style>
    [data-mobile-guard-overlay] {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(10px);
    }

    [data-mobile-guard-overlay].is-visible {
        display: flex;
    }

    .mobile-guard-card {
        width: 100%;
        max-width: 480px;
        border-radius: 28px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.28);
        padding: 28px;
        text-align: center;
    }

    .mobile-guard-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .5rem .85rem;
        background: #fff7ed;
        border: 1px solid #fdba74;
        color: #c2410c;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .mobile-guard-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 88px;
        height: 88px;
        margin-top: 18px;
        border-radius: 999px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #e11d48;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);
    }
</style>
@endpush

<div data-mobile-guard-overlay aria-hidden="true" hidden>
    <div class="mobile-guard-card">
        <div class="mobile-guard-badge">Unsupported Device</div>
        <div class="mobile-guard-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-11 w-11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 1 1-12.728 12.728A9 9 0 0 1 18.364 5.636ZM8.5 8.5l7 7" />
            </svg>
        </div>
        <h2 class="mt-5 text-2xl font-black tracking-tight text-slate-950">EPT Online must be opened on a laptop or desktop.</h2>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const overlay = document.querySelector('[data-mobile-guard-overlay]');

        if (!overlay) {
            return;
        }

        const mobileUAPattern = /Android.+Mobile|iPhone|iPod|Windows Phone|webOS|BlackBerry|Opera Mini|IEMobile|Mobile/i;

        const shouldBlockPhone = () => {
            const userAgent = navigator.userAgent || '';
            const isPhoneUA = mobileUAPattern.test(userAgent);
            const isNarrowViewport = window.matchMedia('(max-width: 820px)').matches;
            const isCoarsePointer = window.matchMedia('(pointer: coarse)').matches;

            return isPhoneUA || (isNarrowViewport && isCoarsePointer);
        };

        const syncGuardState = () => {
            const isBlocked = shouldBlockPhone();

            overlay.classList.toggle('is-visible', isBlocked);
            overlay.hidden = !isBlocked;
            overlay.setAttribute('aria-hidden', isBlocked ? 'false' : 'true');
            document.documentElement.classList.toggle('overflow-hidden', isBlocked);
            document.body.classList.toggle('overflow-hidden', isBlocked);
        };

        syncGuardState();
        window.addEventListener('resize', syncGuardState, { passive: true });
        window.addEventListener('orientationchange', syncGuardState, { passive: true });
    })();
</script>
@endpush
