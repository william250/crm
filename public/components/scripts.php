<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Axios -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<!-- Scripts JavaScript específicos da página -->
<?php if (isset($page_scripts) && is_array($page_scripts)): ?>
    <?php foreach ($page_scripts as $script_file): ?>
        <script src="<?php echo htmlspecialchars($script_file); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Script JavaScript inline específico da página -->
<?php if (isset($inline_script)): ?>
    <script>
        <?php echo $inline_script; ?>
    </script>
<?php endif; ?>