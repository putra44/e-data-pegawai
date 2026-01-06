<footer class="bg-light text-center mt-5 py-3 border-top">

    <!-- BARIS UTAMA: APP NAME + INSTANSI -->
    <small class="text-muted d-block">
        © <?= date('Y'); ?>
        <b><?= htmlspecialchars($settings['app_name'] ?? 'e-DATA Pegawai'); ?></b>

        <?php if (!empty($settings['institution_name'])): ?>
            – <?= htmlspecialchars($settings['institution_name']); ?>
        <?php endif; ?>
    </small>

    <!-- BARIS PALING BAWAH -->
    <small class="text-muted d-block mt-1">
        <?= htmlspecialchars($settings['footer_text'] ?? 'All Right Reverse'); ?>
    </small>

</footer>
