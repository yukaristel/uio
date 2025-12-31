<?php
/**
 * HEADER LAYOUT
 * Step 4/64 (6.3%)
 */
?>
<header>
    <!-- Alert messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-hide alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-hide alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-hide alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i> <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-hide alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill"></i> <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</header>