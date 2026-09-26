<?php
/**
 * Shared confirmation dialog. Any form with data-confirm="…" opens it
 * before submitting (see initConfirm in app.js). Without JavaScript the
 * form simply submits.
 */
?>
<dialog class="dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title">
    <form method="dialog">
        <div class="dialog__body">
            <h2 class="dialog__title" id="confirm-dialog-title" data-confirm-title>Jeni të sigurt?</h2>
            <p class="dialog__text" data-confirm-text></p>
        </div>
        <div class="dialog__actions">
            <button type="submit" class="btn btn--quiet" value="cancel" autofocus>Anulo</button>
            <button type="submit" class="btn btn--danger" value="accept" data-confirm-accept>Po, vazhdo</button>
        </div>
    </form>
</dialog>
