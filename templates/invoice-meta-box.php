<?php if (!defined('ABSPATH')) exit; ?>
<div class="infile-php-meta-box">
    <?php if (!empty($uuid)): ?>
        <p><strong>UUID:</strong><br><code><?php echo esc_html($uuid); ?></code></p>
        <p><strong>Serie / Número:</strong> <?php echo esc_html($serie . ' / ' . $numero); ?></p>
        <p><strong>Status:</strong> <span class="infile-status infile-status-<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></span></p>
        <?php if ($issuedAt): ?>
            <p><strong>Issued at:</strong> <?php echo esc_html($issuedAt); ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p style="color:#999;">No FEL invoice issued for this order.</p>
    <?php endif; ?>
</div>
