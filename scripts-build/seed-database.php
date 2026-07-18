#!/usr/bin/env php
<?php
declare(strict_types=1);

use Tna\Config\DatabaseConfig; use Tna\Database\ConnectionFactory; use Tna\Database\SeederRunner; use Tna\Support\Environment;
$root=dirname(__DIR__); require_once $root.'/scripts-build/bootstrap-db.php';
$command=$argv[1]??''; if(!in_array($command,['check','apply','verify'],true)){fwrite(STDERR,"Usage: php scripts-build/seed-database.php <check|apply|verify>\n"); exit(2);} 
try{ $configPath=Environment::value('TNA_CONFIG_PATH')?:Environment::value('TNA_PRIVATE_CONFIG_PATH')?:$root.'/tna-config.php'; $config=is_file($configPath)?require $configPath:require $root.'/server/config.example.php'; $pdo=(new ConnectionFactory(DatabaseConfig::fromArray($config)))->getConnection(); $runner=new SeederRunner($pdo,$root); if($command==='apply'){$counts=$runner->apply(); echo "Seeds applied.\n";} elseif($command==='verify'){$result=$runner->verify(); foreach($result['checks'] as $n=>$ok){printf("%s %s\n",$ok?'[OK]':'[FAIL]',$n);} $counts=$result['counts']; printCounts($counts); exit($result['ok']?0:1);} else {$counts=$runner->check(); echo "Seed check completed without writes.\n";} printCounts($counts); exit(0);} catch(Throwable $e){fwrite(STDERR,'[ERROR] '.$e->getMessage()."\n"); exit(1);} 
function printCounts(array $c): void { echo "| Entidade | Esperado | Encontrado |\n|---|---:|---:|\n"; foreach(['Controles'=>['controls',32],'Widgets'=>['widgets',4],'Versões'=>['versions',7],'Quadrantes'=>['quadrants',29],'Variantes'=>['variants',203],'Assets'=>['assets',203]] as $label=>$spec){printf("| %s | %d | %d |\n",$label,$spec[1],$c[$spec[0]]??0);} }
