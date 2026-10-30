    </div>
  </main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<script src="<?= url('assets/js/admin.js') ?>"></script>
<?= $extraJs ?? '' ?>
<?= flash_script() ?>
</body>
</html>
