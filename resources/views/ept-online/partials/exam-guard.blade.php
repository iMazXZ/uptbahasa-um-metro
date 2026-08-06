@push('styles')
<style>
    [data-exam-guard],
    [data-exam-guard] * {
        -webkit-user-select: none;
        user-select: none;
        -webkit-touch-callout: none;
    }

    [data-exam-guard] input,
    [data-exam-guard] textarea,
    [data-exam-guard] [contenteditable="true"] {
        -webkit-user-select: text;
        user-select: text;
    }

    [data-exam-guard-warning] {
        position: fixed;
        top: 92px;
        right: 18px;
        z-index: 95;
        width: min(360px, calc(100vw - 36px));
        border-radius: 22px;
        border: 1px solid rgba(248, 113, 113, 0.28);
        background: rgba(15, 23, 42, 0.96);
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.22);
        padding: 16px 18px;
        color: #f8fafc;
        backdrop-filter: blur(14px);
    }

    [data-exam-guard-warning][hidden] {
        display: none !important;
    }

    .exam-guard-warning-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(253, 186, 116, 0.42);
        background: rgba(255, 247, 237, 0.1);
        padding: .35rem .68rem;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #fdba74;
    }

    .exam-guard-warning-title {
        margin-top: 10px;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.4;
        color: #ffffff;
    }

    .exam-guard-warning-body {
        margin-top: 8px;
        font-size: .83rem;
        line-height: 1.65;
        color: #cbd5e1;
    }

    @media print {
        body {
            display: none !important;
        }
    }
</style>
@endpush

<div data-exam-guard-warning hidden aria-live="polite" aria-atomic="true">
    <div class="exam-guard-warning-badge">Exam Security Warning</div>
    <div class="exam-guard-warning-title">Restricted action detected.</div>
    <div class="exam-guard-warning-body">
        This test must remain in its original view. Translation, copy actions, and developer tools are not allowed.
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const root = document.querySelector('[data-exam-guard]');
        let allowUnload = false;

        if (!root) {
            return;
        }

        const integrityUrl = root.dataset.integrityUrl || '';
        const integrityPage = root.dataset.integrityPage || 'exam';
        const integritySection = root.dataset.integritySection || '';
        const tabSwitchGuardEnabled = root.dataset.tabSwitchGuard === '1';
        const tabSwitchLimit = Number.parseInt(root.dataset.tabSwitchLimit || '0', 10) || 0;
        const warningCard = document.querySelector('[data-exam-guard-warning]');
        const warningTitle = warningCard?.querySelector('.exam-guard-warning-title');
        const warningBody = warningCard?.querySelector('.exam-guard-warning-body');
        const tabSwitchModal = document.getElementById('tabSwitchModal');
        const tabSwitchBody = document.getElementById('tabSwitchBody');
        const tabSwitchCountText = document.getElementById('tabSwitchCountText');
        const tabSwitchAcknowledge = document.getElementById('tabSwitchAcknowledge');
        const csrfToken = @json(csrf_token());
        const eventCooldown = new Map();
        const warningState = {
            devtools: false,
            translated: false,
        };
        let tabSwitchCount = 0;
        let lastNotifiedTabSwitchCount = 0;
        let tabSwitchCycleActive = false;
        let tabSwitchRedirecting = false;
        let tabSwitchCycleId = null;
        let tabSwitchHiddenAt = null;

        document.documentElement.classList.add('notranslate');
        document.documentElement.setAttribute('translate', 'no');
        document.body.classList.add('notranslate');
        document.body.setAttribute('translate', 'no');

        const isEditableTarget = (target) => !!target?.closest('input, textarea, [contenteditable="true"]');

        const syncWarningCard = () => {
            if (!warningCard) {
                return;
            }

            if (warningState.translated) {
                warningCard.hidden = false;
                warningTitle.textContent = 'Translation detected.';
                warningBody.textContent = 'This page must stay in its original language during the test. Disable translation and refresh the page if needed.';
                return;
            }

            if (warningState.devtools) {
                warningCard.hidden = false;
                warningTitle.textContent = 'Developer tools detected.';
                warningBody.textContent = 'Close developer tools and continue the test in the normal browser view. This action has been recorded.';
                return;
            }

            warningCard.hidden = true;
        };

        const submitIntegrityEvent = (eventName, context = {}, { cooldownMs = 20000, keepalive = true, useBeacon = true } = {}) => {
            if (!integrityUrl) {
                return Promise.resolve(null);
            }

            const payloadContext = {
                page: integrityPage,
                section: integritySection,
                ...context,
            };

            const payloadKey = `${eventName}:${JSON.stringify(payloadContext)}`;
            const now = Date.now();
            const lastSentAt = eventCooldown.get(payloadKey) ?? 0;

            if ((now - lastSentAt) < cooldownMs) {
                return Promise.resolve(null);
            }

            eventCooldown.set(payloadKey, now);

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('event', eventName);

            Object.entries(payloadContext).forEach(([key, value]) => {
                if (value === undefined || value === null) {
                    return;
                }

                formData.append(`context[${key}]`, String(value));
            });

            if (useBeacon && navigator.sendBeacon) {
                navigator.sendBeacon(integrityUrl, formData);
                return Promise.resolve(null);
            }

            return window.fetch(integrityUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                credentials: 'same-origin',
                keepalive,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        return null;
                    }

                    return response.json().catch(() => null);
                })
                .catch(() => null);
        };

        const sendIntegrityEvent = (eventName, context = {}, cooldownMs = 20000) => {
            void submitIntegrityEvent(eventName, context, { cooldownMs });
        };

        const blockEvent = (event) => {
            if (isEditableTarget(event.target)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            sendIntegrityEvent(`${event.type}_blocked`, {
                tag: event.target?.tagName || 'unknown',
            }, 15000);
        };

        const cleanupTranslationArtifacts = () => {
            document.querySelectorAll(
                'iframe.goog-te-banner-frame, .goog-te-banner-frame, .skiptranslate, [class*="goog-te"], [id*="goog-gt-"]'
            ).forEach((node) => {
                if (node instanceof HTMLElement) {
                    node.style.display = 'none';
                    node.setAttribute('aria-hidden', 'true');
                }
            });
        };

        const detectTranslation = () => {
            cleanupTranslationArtifacts();

            return document.documentElement.classList.contains('translated-ltr')
                || document.documentElement.classList.contains('translated-rtl')
                || !!document.querySelector('iframe.goog-te-banner-frame, .goog-te-banner-frame, .skiptranslate, [class*="goog-te"], [id*="goog-gt-"]');
        };

        const detectDevtools = () => {
            const widthGap = Math.max(0, window.outerWidth - window.innerWidth);
            const heightGap = Math.max(0, window.outerHeight - window.innerHeight);
            const detected = widthGap > 160 || heightGap > 160;

            return { detected, widthGap, heightGap };
        };

        const syncTranslationState = () => {
            const translated = detectTranslation();

            if (translated && !warningState.translated) {
                sendIntegrityEvent('translation_detected', {
                    reason: 'translate_dom_injected',
                }, 60000);
            }

            warningState.translated = translated;
            syncWarningCard();
        };

        const syncDevtoolsState = () => {
            const state = detectDevtools();

            if (state.detected && !warningState.devtools) {
                sendIntegrityEvent('devtools_suspected', {
                    width_gap: state.widthGap,
                    height_gap: state.heightGap,
                }, 60000);
            }

            warningState.devtools = state.detected;
            syncWarningCard();
        };

        const closeTabSwitchModal = () => {
            if (!tabSwitchModal) {
                return;
            }

            tabSwitchModal.classList.add('hidden');
            tabSwitchModal.classList.remove('flex');
        };

        const showTabSwitchModal = (count) => {
            if (!tabSwitchModal || count <= lastNotifiedTabSwitchCount) {
                return;
            }

            lastNotifiedTabSwitchCount = count;

            if (tabSwitchBody) {
                tabSwitchBody.textContent = 'You left the test tab. Stay on this page while the test is in progress.';
            }

            if (tabSwitchCountText) {
                const remaining = Math.max(0, tabSwitchLimit - count);
                tabSwitchCountText.textContent = remaining > 0
                    ? `Warning ${count} of ${tabSwitchLimit}. ${remaining} more violation${remaining === 1 ? '' : 's'} will cause automatic submission.`
                    : `The tab-switch limit has been reached. The test will be submitted automatically.`;
            }

            tabSwitchModal.classList.remove('hidden');
            tabSwitchModal.classList.add('flex');
        };

        const allowExamUnload = () => {
            if (typeof window.__eptAllowUnload === 'function') {
                window.__eptAllowUnload();
                return;
            }

            window.dispatchEvent(new CustomEvent('ept:allow-unload'));
        };

        const handleTabSwitchPayload = (payload) => {
            if (!payload) {
                return;
            }

            if (typeof payload.tab_switch_count === 'number') {
                tabSwitchCount = payload.tab_switch_count;
            }

            if (payload.redirect) {
                tabSwitchRedirecting = true;
                closeTabSwitchModal();
                allowExamUnload();
                window.location.href = payload.redirect;
                return;
            }

            if (document.visibilityState === 'visible' && tabSwitchCount > 0) {
                showTabSwitchModal(tabSwitchCount);
            }
        };

        ['copy', 'cut', 'paste', 'contextmenu', 'selectstart', 'dragstart'].forEach((eventName) => {
            document.addEventListener(eventName, blockEvent, true);
        });

        document.addEventListener('keydown', (event) => {
            const ctrlOrMeta = event.ctrlKey || event.metaKey;
            const key = (event.key || '').toLowerCase();
            const shouldBlock =
                event.key === 'F5'
                || event.key === 'F12'
                || (ctrlOrMeta && ['r', 'u', 'p', 's', 'a', 'c', 'x', 'v'].includes(key))
                || (ctrlOrMeta && event.shiftKey && ['i', 'j', 'c', 'r'].includes(key));

            if (!shouldBlock) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            sendIntegrityEvent('shortcut_blocked', {
                key: event.key || '',
                shortcut: `${ctrlOrMeta ? 'mod+' : ''}${event.shiftKey ? 'shift+' : ''}${key}`,
            }, 15000);
        }, true);

        document.addEventListener('click', (event) => {
            const allowTarget = event.target.closest('[data-allow-unload="1"]');

            if (!allowTarget) {
                return;
            }

            allowUnload = true;
        }, true);

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form');

            if (!form || form.dataset.allowUnload !== '1') {
                return;
            }

            allowUnload = true;
        }, true);

        window.__eptAllowUnload = () => {
            allowUnload = true;
        };

        window.addEventListener('ept:allow-unload', () => {
            allowUnload = true;
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                if (allowUnload || tabSwitchRedirecting) {
                    return;
                }

                if (tabSwitchGuardEnabled && !tabSwitchCycleActive) {
                    tabSwitchCycleActive = true;
                    tabSwitchCycleId = `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
                    tabSwitchHiddenAt = Date.now();

                    sendIntegrityEvent('visibility_hidden', {
                        visibility: document.visibilityState,
                        reason: 'tab_switch',
                        cycle_id: tabSwitchCycleId,
                    }, 0);
                } else {
                    sendIntegrityEvent('visibility_hidden', {
                        visibility: document.visibilityState,
                    }, 15000);
                }
            }

            if (document.visibilityState === 'visible' && tabSwitchGuardEnabled && tabSwitchCycleActive && !tabSwitchRedirecting) {
                const cycleId = tabSwitchCycleId;
                const hiddenMs = tabSwitchHiddenAt ? Math.max(0, Date.now() - tabSwitchHiddenAt) : 0;

                tabSwitchCycleActive = false;
                tabSwitchCycleId = null;
                tabSwitchHiddenAt = null;

                void submitIntegrityEvent('tab_switch_violation', {
                    visibility: document.visibilityState,
                    reason: 'tab_switch',
                    cycle_id: cycleId,
                    hidden_ms: hiddenMs,
                }, {
                    cooldownMs: 0,
                    keepalive: false,
                    useBeacon: false,
                }).then(handleTabSwitchPayload);
            }
        });

        window.addEventListener('blur', () => {
            sendIntegrityEvent('window_blur', {
                reason: 'window_blur',
            }, 15000);
        });

        window.addEventListener('beforeunload', (event) => {
            if (allowUnload) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });

        const observer = new MutationObserver(() => {
            window.requestAnimationFrame(syncTranslationState);
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'lang'],
        });

        syncTranslationState();
        syncDevtoolsState();

        window.setInterval(syncDevtoolsState, 1500);
        window.setInterval(syncTranslationState, 2500);

        tabSwitchAcknowledge?.addEventListener('click', () => {
            closeTabSwitchModal();
        });

        tabSwitchModal?.addEventListener('click', (event) => {
            if (event.target === tabSwitchModal) {
                closeTabSwitchModal();
            }
        });
    })();
</script>
@endpush
