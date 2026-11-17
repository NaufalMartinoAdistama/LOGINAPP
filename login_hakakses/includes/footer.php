<?php
/**
 * includes/footer.php
 * Wrapper Footer Global: Penutup Layout, Footer Info, dan Skrip JS
 */
?>
        </main>

        <!-- Footer Hak Cipta -->
        <footer class="py-3 px-4 bg-white border-top text-muted small">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> <strong><?= APP_TITLE ?></strong>. Seluruh Hak Cipta Dilindungi.</span>
                <div class="text-secondary">
                    <span>Versi 1.0.0 &bull; PHP Native PDO & RBAC</span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5.3.3 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom App JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

</body>
</html>
