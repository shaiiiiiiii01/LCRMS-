<?php
$footerStyle = $footerStyle ?? 'standard';
$footerStatusDelayed = $footerStatusDelayed ?? false;
$footerStatusLabel = $footerStatusLabel ?? '';
$footerStatusText = $footerStatusText ?? 'LCRMS_v1.0.0';
$footerStatusClass = $footerStatusClass ?? '';
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
            <span><?php echo htmlspecialchars($footerStatusLabel); ?><span class="<?php echo htmlspecialchars($footerStatusClass); ?>"><?php echo htmlspecialchars($footerStatusText); ?></span></span>
        </div>
    <?php endif; ?>
</footer>

