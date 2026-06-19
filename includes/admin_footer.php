<?php
$footerStyle = $footerStyle ?? 'standard';
$footerStatusDelayed = $footerStatusDelayed ?? false;
$footerStatusText = $footerStatusDelayed ? 'Offline' : 'Online';
$footerStatusClass = $footerStatusDelayed ? 'system-status-offline' : 'system-status-online';
?>

<footer class="admin-footer <?php echo $footerStyle === 'compact' ? 'is-compact' : ''; ?>">
    <?php if ($footerStyle === 'compact'): ?>
        <p>&copy; 2026 Barangay Old Cabalan. All Rights Reserved.</p>
        <div class="admin-footer-links">
            <a href="#">Terms of Service</a>
            <a href="#">Support Desk</a>
        </div>
    <?php else: ?>
        <p>&copy; 2026 Barangay Old Cabalan. All Rights Reserved.</p>
        <div class="admin-footer-links">
            <span>System Status: <span class="<?php echo $footerStatusClass; ?>"<?php echo $footerStatusDelayed ? ' data-login-system-status' : ''; ?>><?php echo $footerStatusText; ?></span></span>
        </div>
    <?php endif; ?>
</footer>
