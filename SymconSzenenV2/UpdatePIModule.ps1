## Backup old pimodule
$uid = New-Guid;
$nme = (get-item $PSScriptRoot ).parent;
$modulePIModule = $PSScriptRoot + "\pimodule.php";
$newPath = "C:\PIModuleCreator\Module Backups\pimodule-" + $nme + "-" + (Get-Date).Day + "-" + (Get-Date).Month + "-" + (Get-Date).Year + "--" + $uid + ".php";
$actualPIModule = "C:\PIModuleCreator\pimodule.php";


Move-Item -Path $modulePIModule -Destination $newPath;



Copy-Item $actualPIModule -Destination $modulePIModule;