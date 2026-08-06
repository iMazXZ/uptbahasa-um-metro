<div class="space-y-3">
    @if($registrations->isEmpty())
        <div style="text-align: center; padding: 24px; color: #64748b;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 8px; color: #cbd5e1;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            <p>Belum ada peserta di grup ini</p>
        </div>
    @else
        <div style="background: #f8fafc; border-radius: 8px; padding: 12px; font-size: 14px; color: #475569;">
            Total: <strong>{{ $registrations->count() }}</strong> peserta
        </div>
        <div style="max-height: 384px; overflow-y: auto;">
            @foreach($registrations as $reg)
                <div style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; color: #2563eb; font-weight: 600; font-size: 14px; flex-shrink: 0;">
                        {{ strtoupper(substr($reg->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <p style="margin: 0; font-weight: 500; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $reg->user->name ?? '-' }}</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">{{ $reg->user->srn ?? '-' }} • {{ $reg->user->prody->name ?? '-' }}</p>
                    </div>
                    @if($reg->user->whatsapp && $reg->user->whatsapp_verified_at)
                        <button type="button" 
                                data-url="{{ route('admin.ept-group.send-wa-single', ['group' => $groupId, 'registration' => $reg->id]) }}"
                                data-token="{{ csrf_token() }}"
                                style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; background: #10b981; border: none; color: white; font-size: 12px; font-weight: 600; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 14px; height: 14px;">
                                <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.155.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.288Z" />
                            </svg>
                            <span>Kirim WA</span>
                        </button>
                    @else
                        <span style="font-size: 12px; color: #94a3b8; padding: 0 8px;">No WA</span>
                    @endif
                </div>
            @endforeach
        </div>
        
        <script>
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('button[data-url]');
                if (!btn) return;
                
                e.preventDefault();
                btn.disabled = true;
                const span = btn.querySelector('span');
                const originalText = span.textContent;
                span.textContent = 'Mengirim...';
                btn.style.opacity = '0.6';
                
                fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': btn.dataset.token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        span.textContent = 'Terkirim ✓';
                        btn.style.background = '#059669';
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    span.textContent = 'Gagal!';
                    btn.style.background = '#ef4444';
                    setTimeout(() => {
                        btn.disabled = false;
                        span.textContent = originalText;
                        btn.style.background = '#10b981';
                        btn.style.opacity = '1';
                    }, 2000);
                });
            });
        </script>
    @endif
</div>
