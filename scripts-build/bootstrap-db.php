<?php
declare(strict_types=1);
$autoload = $root . '/server/vendor/autoload.php';
if (is_file($autoload)) { require $autoload; } else { spl_autoload_register(static function (string $class) use ($root): void { $prefix='Tna\\'; if(!str_starts_with($class,$prefix)){return;} $file=$root.'/server/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($file)){require $file;} }); }
