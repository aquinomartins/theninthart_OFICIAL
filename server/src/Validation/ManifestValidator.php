<?php
declare(strict_types=1);
namespace Tna\Validation;
use RuntimeException;
final class ManifestValidator
{
    public function loadAll(string $root): array
    {
        $out=[]; foreach(['story-controls','story-widgets','story-versions','quadrants','quadrant-slots'] as $name){$path="$root/data/$name.json"; $json=json_decode((string)file_get_contents($path),true); if(!is_array($json)||!is_array($json['items']??null)){throw new RuntimeException("Invalid manifest: $path");} $out[$name]=$json;} $this->assertCounts($out); return $out;
    }
    public function assertCounts(array $m): void
    {
        foreach(['story-controls'=>32,'story-widgets'=>4,'story-versions'=>7,'quadrants'=>29,'quadrant-slots'=>203] as $n=>$c){if(count($m[$n]['items']??[])!==$c){throw new RuntimeException("$n expected $c items.");}}
        $slots=$m['quadrant-slots']['items']; if(($slots[0]['id']??null)!=='q01-v01'||($slots[202]['id']??null)!=='q29-v07'){throw new RuntimeException('Slot boundaries must be q01-v01 and q29-v07.');}
        $seen=[]; foreach($slots as $slot){$q=(int)$slot['quadrantNumber']; $v=(int)$slot['versionNumber']; $id=sprintf('q%02d-v%02d',$q,$v); $pos=(($q-1)*7)+$v; if(($slot['id']??'')!==$id||(int)$slot['position']!==$pos){throw new RuntimeException("Invalid slot formula for {$slot['id']}.");} if(isset($seen[$id])){throw new RuntimeException("Duplicated slot $id.");} $seen[$id]=true;}
    }
}
