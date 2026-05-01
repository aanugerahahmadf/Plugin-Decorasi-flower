<!-- Midtrans Snap Script - Optimized for Filament -->
<script type="text/javascript"
  src="{{ config('midtrans.snap_url') }}"
  data-client-key="{{ config('midtrans.client_key') }}"></script>

<script type="text/javascript">
  // Mendengarkan perintah bayar dari aplikasi
  window.addEventListener('open-midtrans-snap', function (event) {
    const detail = event.detail || {};
    const token = detail.token || (Array.isArray(detail) ? detail[0]?.token : null);

    if (token) {
      console.log('[Midtrans] Membuka Pembayaran dengan Token:', token);
      window.snap.pay(token, {
        onSuccess: function(result) {
          console.log('[Midtrans] Success:', result);
          alert("{{ __('Pembayaran Berhasil!') }}");
          // Redirect ke halaman pesanan saya
          window.location.href = "{{ \Aanugerah\WeddingPro\Filament\Resources\OrderResource::getUrl() }}";
        },
        onPending: function(result) {
          console.log('[Midtrans] Pending:', result);
          alert("{{ __('Menunggu pembayaran Anda!') }}");
          window.location.href = "{{ \Aanugerah\WeddingPro\Filament\Resources\OrderResource::getUrl() }}";
        },
        onError: function(result) {
          console.log('[Midtrans] Error:', result);
          alert("{{ __('Pembayaran Gagal!') }}");
        },
        onClose: function() {
          console.log('[Midtrans] Modal ditutup tanpa menyelesaikan pembayaran');
          // Optional: You could reload or redirect, but doing nothing is fine too
        }
      });
    } else {
      console.error('[Midtrans] Error: Token tidak ditemukan!');
    }
  });
</script>