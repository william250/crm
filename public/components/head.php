<?php
// Definir título da página se não foi definido
if (!isset($page_title)) {
    $page_title = 'CloutHub';
}
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?></title>

<!-- Favicon -->
<link rel="icon" type="image/png" href="assets/images/favicon.png">

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Chart.js (carregado condicionalmente) -->
<?php if (isset($include_chartjs) && $include_chartjs): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<!-- Custom CSS Variables -->
<style>
:root {
    --primary-color: #CA773B;
    --primary-hover: #B8692F;
    --primary-light: #E8A76B;
    --primary-dark: #A0612A;
}
</style>

<!-- Custom CSS -->
<link href="assets/css/dashboard.css" rel="stylesheet">

<!-- CSS adicional específico da página -->
<?php if (isset($additional_css) && is_array($additional_css)): ?>
    <?php foreach ($additional_css as $css_file): ?>
        <link href="<?php echo htmlspecialchars($css_file); ?>" rel="stylesheet">
    <?php endforeach; ?>
<?php endif; ?>