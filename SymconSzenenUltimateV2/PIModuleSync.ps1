## Backup old pimodule
$uid = New-Guid;
$nme = (get-item $PSScriptRoot ).parent;
$newPath = "C:\PIModuleCreator\Backups\pimodule-" + $nme + "-" + (Get-Date).Day + "-" + (Get-Date).Month + "-" + (Get-Date).Year + "--" + $uid + ".php";
$oldPath = "C:\PIModuleCreator\pimodule.php";

Move-Item -Path $oldPath -Destination $newPath;

## Get New Item
$modulePath = $PSScriptRoot + "\pimodule.php";

Copy-Item $modulePath -Destination $oldPath;