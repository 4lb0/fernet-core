<?php

use Fernet\Component\FernetLogo;
use Fernet\Component\LiveReload;
use Fernet\Framework;

?>

<?php echo new LiveReload(Framework::getInstance()); ?>

<header style="padding: 35px 20px">
    <div style="float:left; margin-right: 10px">
        <a href="<?php echo Framework::URL; ?>" target="_blank">
            <?php echo new FernetLogo('80', '80', '#FFFFFF'); ?>
        </a>
        <h1>
            <a href="<?php echo Framework::URL; ?>" target="_blank" style="color:white">
                Fernet
            </a>
        </h1>
    </div>
<?php
// Render Whoops error
if (isset($tpl, $header)) {
    $tpl->render($header);
} ?>
</header>