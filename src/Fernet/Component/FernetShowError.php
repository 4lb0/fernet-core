<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Framework;
use Fernet\Core\Exception;
use Throwable;

class FernetShowError
{
    private const TITLE = 'Error on Fernet';
    public Throwable $error;
    private string $rootPath;

    public function __construct(Framework $fernet)
    {
        $this->rootPath = rtrim($fernet->getConfig('rootPath'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    private function path(string $path, int $line): string
    {
        $fullPath = $path;
        if (0 === strpos($path, $this->rootPath)) {
            $path = substr($path, strlen($this->rootPath));
        }
        $url = "subl://$fullPath:$line";

        return "File <strong><a href=\"$url\">$path</a></strong> on line: $line";
    }

    public function __toString(): string
    {
        ob_start(); ?>
        <html lang="en">
        <head>
            <title><?php echo self::TITLE; ?>:  <?php echo $this->error->getMessage(); ?></title>
            <FernetFavicon />
            <FernetStylesheet />
        </head>
        <body class="show-error">
            <h1>
                <div class="icon baseline">
                    <FernetLogo height="50" width="50" />
                </div>
                <?php echo self::TITLE; ?>
            </h1>
            <h2><?php echo $this->error->getMessage(); ?></h2>

            <?php if ($this->error instanceof Exception && $this->error->getLink()) { ?>
            <p>For more help go to <a href="<?php echo $this->error->getLink(); ?>"><?php echo $this->error->getLink(); ?></a></p>
            <?php } ?> 
            <p>
                <?php echo $this->path($this->error->getFile(), $this->error->getLine()); ?>
            </p>
            <h3>Stack trace</h3>
            <ol>
            <?php foreach ($this->error->getTrace() as $trace) { ?>
                <li>
                    <p><strong><?php echo $trace['class'] ?? ''; ?><?php echo $trace['type'] ?? ''; ?><?php echo $trace['function'].'()' ?? ''; ?></strong></p>
                    <?php if (isset($trace['file'])) { ?>
                        <p><?php echo $this->path($trace['file'], $trace['line']); ?></p>
                    <?php } ?>
                </li>
            <?php } ?>
            </ol>
            <?php foreach (['ENV', 'SERVER', 'POST', 'GET', 'SESSION'] as $var) { ?>
            <h3><?php echo $var; ?></h3>
            <ul>
            <?php foreach ($GLOBALS["_$var"] as $key => $value) { ?>
                <li><strong><?php echo $key; ?></strong> <?php echo var_export($value, true); ?></li>
            <?php } ?>
            </ul>
            <?php } ?>
        </body>
        </html>
<?php
        return ob_get_clean();
    }
}
