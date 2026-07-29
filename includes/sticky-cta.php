<?php
/**
 * Sticky CTA для мобильных устройств
 */
function renderStickyCta(array $config): string {
    $href = $config['href'] ?? '#';
    $label = $config['label'] ?? 'Открыть';
    $sub = $config['sub'] ?? '';
    $variant = $config['variant'] ?? 'accent'; // accent|primary
    $external = !empty($config['external']);
    $id = $config['id'] ?? 'sticky-cta';

    $bg = $variant === 'primary' ? '#1a56db' : '#059669';
    $rel = $external ? 'noopener noreferrer nofollow sponsored' : '';
    $target = $external ? '_blank' : '';

    ob_start();
    ?>
    <div id="<?= e($id) ?>-wrap" class="lg:hidden" style="position:fixed;left:0;right:0;bottom:0;z-index:9995;padding:12px;background:linear-gradient(180deg,rgba(249,250,251,0) 0%,rgba(249,250,251,.92) 18%,rgba(249,250,251,1) 100%);backdrop-filter:blur(6px);">
        <div style="max-width:720px;margin:0 auto;">
            <a href="<?= e($href) ?>" <?= $target ? 'target="'.$target.'"' : '' ?> <?= $rel ? 'rel="'.$rel.'"' : '' ?> style="display:flex;align-items:center;justify-content:space-between;gap:12px;background:<?= e($bg) ?>;color:#fff;padding:14px 16px;border-radius:16px;text-decoration:none;box-shadow:0 12px 30px rgba(15,23,42,.18);font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
                <div style="min-width:0;">
                    <div style="font-size:15px;font-weight:700;line-height:1.2"><?= e($label) ?></div>
                    <?php if ($sub): ?><div style="font-size:12px;opacity:.9;line-height:1.3;margin-top:2px"><?= e($sub) ?></div><?php endif; ?>
                </div>
                <span style="font-size:18px;font-weight:700;flex-shrink:0">→</span>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
