<?php
$footerStyle = $footerStyle ?? 'standard';
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
            <span>System Status: <span class="system-status-online">Online</span></span>
        </div>
    <?php endif; ?>
</footer>
