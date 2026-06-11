<?php
$footerStyle = $footerStyle ?? 'standard';
?>

<footer class="admin-footer <?php echo $footerStyle === 'compact' ? 'is-compact' : ''; ?>">
    <?php if ($footerStyle === 'compact'): ?>
        <p>&copy; 2026 LCRMS | Lupong Case and Record Management System</p>
        <div class="admin-footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Support Desk</a>
        </div>
    <?php else: ?>
        <p>&copy; 2024 Lupong Case and Record Management System. All Rights Reserved.</p>
        <div class="admin-footer-links">
            <a href="#">Privacy Policy</a>
            <span>System Status: Online</span>
        </div>
    <?php endif; ?>
</footer>
