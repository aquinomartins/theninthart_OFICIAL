<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$release = $root . '/release';
$command = $argv[1] ?? 'check';
$force = in_array('--force', $argv, true);
$errors = [];
$warnings = [];

function say(string $message): void { echo $message . PHP_EOL; }
function err(string $message): void { global $errors; $errors[] = $message; }
function warnx(string $message): void { global $warnings; $warnings[] = $message; }
function read_json_file(string $path): array { $data = json_decode((string) file_get_contents($path), true); return is_array($data) ? $data : []; }
function copy_file_safe(string $from, string $to): void { if (!is_file($from)) { return; } @mkdir(dirname($to), 0775, true); copy($from, $to); }
function copy_dir_safe(string $from, string $to, array $deny = []): void {
    if (!is_dir($from)) { return; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($from) + 1));
        foreach ($deny as $pattern) { if (fnmatch($pattern, basename($rel)) || fnmatch($pattern, $rel)) { continue 2; } }
        if ($file->isDir()) { continue; }
        copy_file_safe($file->getPathname(), $to . '/' . $rel);
    }
}
function rm_dir(string $dir): void { if (!is_dir($dir)) { return; } $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); } rmdir($dir); }
function required_file(string $path): void { global $root; if (!is_file($root . '/' . $path)) { err("Arquivo obrigatório ausente: $path"); } }
function count_items(string $file, int $expected, string $label): int { global $root; $json = read_json_file($root . '/data/' . $file); $count = count($json['items'] ?? []); if ($count !== $expected) { err("$label esperado $expected, encontrado $count"); } return $count; }
function php_lint_all(string $base): void { $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)); foreach ($it as $f) { if ($f->isFile() && $f->getExtension() === 'php') { exec('php -l ' . escapeshellarg($f->getPathname()) . ' 2>&1', $out, $code); if ($code !== 0) { err('PHP lint falhou: ' . $f->getPathname()); } } } }
function forbidden_public_files(string $public): array { $bad = []; if (!is_dir($public)) { return ['public_html ausente']; } $patterns = ['node_modules','frontend','tests','docs','database','scripts-build','release','server','.env','package-lock.json','angular.json','tsconfig.json','*.ts','*.map']; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($public, FilesystemIterator::SKIP_DOTS)); foreach ($it as $f) { $rel = str_replace('\\','/',substr($f->getPathname(), strlen($public)+1)); foreach ($patterns as $p) { if (fnmatch($p, $rel) || fnmatch($p, basename($rel))) { $bad[] = $rel; } } } return $bad; }
function build_checksums(string $release): void { $lines=[]; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($release, FilesystemIterator::SKIP_DOTS)); foreach ($it as $f) { if (!$f->isFile()) { continue; } $rel=str_replace('\\','/',substr($f->getPathname(),strlen($release)+1)); if ($rel==='checksums.sha256' || str_ends_with($rel, '/.gitkeep')) { continue; } $lines[] = hash_file('sha256',$f->getPathname()) . '  ' . $rel; } sort($lines); file_put_contents($release.'/checksums.sha256', implode(PHP_EOL,$lines).PHP_EOL); }
function verify_checksums(string $release): void { $file=$release.'/checksums.sha256'; if(!is_file($file)){err('checksums.sha256 ausente');return;} foreach(file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){[$hash,$rel]=explode('  ',$line,2); $path=$release.'/'.$rel; if(!is_file($path)||hash_file('sha256',$path)!==$hash){err('Checksum inválido: '.$rel);} } }
function check_all(bool $releaseMode=false): void {
    global $root, $release;
    foreach (['index.html','experience.html','tempo/index.html','styles.css','script.js','api/index.php','api/.htaccess','.htaccess','server/config.example.php'] as $f) { required_file($f); }
    $c=count_items('story-controls.json',32,'Controles'); $w=count_items('story-widgets.json',4,'Widgets'); $v=count_items('story-versions.json',7,'Versões'); $q=count_items('quadrants.json',29,'Quadrantes'); $s=count_items('quadrant-slots.json',203,'Slots');
    if (!is_dir($root . '/assets/story-engine') || !is_file($root . '/assets/story-engine/story-engine-assets.json')) { err('Build Angular/manifesto assets/story-engine ausente.'); }
    $assets = read_json_file($root . '/assets/story-engine/story-engine-assets.json');
    foreach (array_merge($assets['scripts'] ?? [], $assets['styles'] ?? []) as $asset) { $assetPath = str_starts_with($asset, '/assets/story-engine/') ? $root . $asset : $root . '/assets/story-engine/' . ltrim($asset, '/'); if (!is_file($assetPath)) { err('Bundle Angular referenciado não existe: ' . $asset); } if (str_contains($asset, '.map')) { err('Source map público no manifesto Angular: ' . $asset); } }
    foreach (['database/migrations','database/seeds'] as $d) { if (!is_dir($root.'/'.$d) || count(glob($root.'/'.$d.'/*')) === 0) { err('Diretório obrigatório vazio/ausente: '.$d); } }
    php_lint_all($root . '/api'); php_lint_all($root . '/server'); php_lint_all($root . '/scripts-build'); php_lint_all($root . '/database');
    $suspects=[]; foreach(['.env','server/config.php','config.php','tna-config.php'] as $f){ if(is_file($root.'/'.$f)){$suspects[]=$f;} } if($suspects){ err('Possível configuração real/segredo versionável presente: '.implode(', ',$suspects)); }
    if ($releaseMode) { foreach (forbidden_public_files($release.'/public_html') as $bad) { err('Arquivo proibido em public_html: '.$bad); } verify_checksums($release); }
    say("Catálogo: controles=$c widgets=$w versões=$v quadrantes=$q slots=$s");
}

if (!in_array($command, ['check','build','verify'], true)) { fwrite(STDERR, "Uso: php scripts-build/package-shared-hosting.php check|build|verify [--force]\n"); exit(2); }
if ($command === 'check') { check_all(false); }
if ($command === 'build') {
    check_all(false);
    if ($errors) { goto finish; }
    if (is_dir($release)) { if (!$force) { err('release/ já existe; use --force para reconstruir.'); goto finish; } rm_dir($release); }
    @mkdir($release.'/public_html',0775,true); @mkdir($release.'/private/server',0775,true); @mkdir($release.'/private/logs',0775,true); @mkdir($release.'/database',0775,true); @mkdir($release.'/documentation',0775,true);
    foreach (['index.html','experience.html','.htaccess','styles.css','script.js'] as $f) { copy_file_safe($root.'/'.$f,$release.'/public_html/'.$f); }
    foreach (['styles','scripts','data','contracts','assets','tempo','api'] as $d) { copy_dir_safe($root.'/'.$d,$release.'/public_html/'.$d, ['*.ts','*.map']); }
    copy_dir_safe($root.'/server',$release.'/private/server',['vendor/bin/*']); copy_file_safe($root.'/server/config.example.php',$release.'/private/config.example.php'); file_put_contents($release.'/private/logs/.gitkeep','');
    copy_dir_safe($root.'/database/migrations',$release.'/database/migrations'); copy_dir_safe($root.'/database/seeds',$release.'/database/seeds');
    copy_file_safe($root.'/docs/DEPLOY-SUPERDOMINIOS.md',$release.'/documentation/DEPLOY-SUPERDOMINIOS.md'); copy_file_safe($root.'/docs/ROLLBACK.md',$release.'/documentation/ROLLBACK.md'); copy_file_safe($root.'/docs/RELEASE-TEST-REPORT.md',$release.'/documentation/RELEASE-TEST-REPORT.md');
    foreach (['DEPLOY-CHECKLIST.md','POST-DEPLOY-TESTS.md','TROUBLESHOOTING.md'] as $f) { copy_file_safe($root.'/docs/'.$f,$release.'/documentation/'.$f); }
    copy_file_safe($root.'/docs/DEPLOY-CHECKLIST.md',$release.'/DEPLOY-CHECKLIST.md');
    file_put_contents($release.'/private/README-PRIVATE-FILES.md', "# Arquivos privados\n\nColoque este diretório fora de public_html quando o cPanel permitir. Copie config.example.php para config.php/tna-config.php somente no servidor e preencha credenciais localmente.\n");
    file_put_contents($release.'/database/README-DATABASE-INSTALL.md', "# Banco de dados\n\nExecute migrations e seeds por Terminal do cPanel ou procedimento controlado descrito em documentation/DEPLOY-SUPERDOMINIOS.md. Não há dumps reais neste pacote.\n");
    $git=trim((string)shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD 2>/dev/null')) ?: null; $php=PHP_VERSION;
    $files=[]; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($release,FilesystemIterator::SKIP_DOTS)); foreach($it as $f){ if($f->isFile()){$files[]=str_replace('\\','/',substr($f->getPathname(),strlen($release)+1));}} sort($files);
    file_put_contents($release.'/release-manifest.json', json_encode(['application'=>'the-ninth-art','releaseVersion'=>gmdate('Ymd-His'),'generatedAt'=>gmdate(DATE_ATOM),'gitCommit'=>$git,'environment'=>'production','requirements'=>['php'=>$php,'pdoMysql'=>extension_loaded('pdo_mysql'),'apacheRewrite'=>true,'mysqlOrMariaDb'=>true,'nodeRuntime'=>false],'catalog'=>['controls'=>32,'widgets'=>4,'versions'=>7,'quadrants'=>29,'slots'=>203],'build'=>['angular'=>read_json_file($root.'/assets/story-engine/story-engine-assets.json'),'php'=>['syntax'=>'checked'],'assets'=>['publicPath'=>'/assets/story-engine/']],'files'=>$files,'metadata'=>['sharedHosting'=>'cPanel/Apache']], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
    build_checksums($release); say('Release criada em '.$release);
}
if ($command === 'verify') { check_all(true); }
finish:
foreach ($warnings as $w) { fwrite(STDERR, "[WARN] $w\n"); }
if ($errors) { foreach ($errors as $e) { fwrite(STDERR, "[ERRO] $e\n"); } exit(1); }
say('[OK] package-shared-hosting '.$command.' concluído');
