<?php
/** Mensaje flash del backend (contrato §2), en el mismo aviso flotante de la maqueta. Se oculta solo a los pocos segundos. */
if (($flash ?? null) !== null): ?>
    <div id="toast" class="show flash flash-<?= e($flash['tipo']) ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['mensaje']) ?></div>
<?php else: ?>
    <div id="toast" role="status" aria-live="polite"></div>
<?php endif;
