<?

    require(__DIR__ . "\\pimodule.php");

    // Klassendefinition
    class SymconSzenenV2 extends PISymconModule {
 
        public $sensorOld = null;

        public $Details = true;

        public $GeräteFolder = null;

        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);

            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {

            parent::Create();
 
        }
 
        protected function setExcludedHide () {

            return array($this->detailsVar, $this->AutomatikVar, $this->SperreVar, $this->searchObjectByName("Szene"), $this->searchObjectByName("Status"));
    
        }

        protected function setExcludedShow () { 

            $allScenes = $this->getAllScenesSorted();

            if ($allScenes != null) {

                if ($this->arrayNotEmpty($allScenes)) {

                    $sceneID = $this->searchObjectByName($allScenes[0]);
                    return array("instance", "script", $this->searchObjectByName("SceneData"), $this->searchObjectByName("Geräte"), $this->searchObjectByName("LastScene"), $sceneID, $this->searchObjectByName("SceneHashList"));

                } else {

                    return array("instance", "script", $this->searchObjectByName("SceneData"), $this->searchObjectByName("Geräte"), $this->searchObjectByName("LastScene"));

                }

            } else {

                return array("instance", "script", $this->searchObjectByName("SceneData"), $this->searchObjectByName("Geräte"), $this->searchObjectByName("LastScene"));

            }

    
        }

        protected function onDetailsChangeHide () {
            
            $sensorSet = false;
            $sensorID = $this->ReadPropertyInteger("Sensor");
            $prnt = IPS_GetParent($this->InstanceID);

            $name = IPS_GetName($this->InstanceID);

            if ($sensorID != null) {

                $sensorSet = true;

            }

            // Targets ausblenden
            $this->deleteObject($this->searchObjectByRealName($name . " Geräte", $prnt));

            if ($sensorSet) {

                $this->deleteObject($this->searchObjectByName($name . " DaySets", $prnt));

            }

        }

        protected function onDetailsChangeShow () {

            $sensorSet = false;
            $sensorID = $this->ReadPropertyInteger("Sensor");
            $prnt = IPS_GetParent($this->InstanceID);

            $name = IPS_GetName($this->InstanceID);

            if ($sensorID != null) {

                $sensorSet = true;

            }

            $this->linkFolderMobile($this->searchObjectByName("Targets"), $name . " Geräte", $prnt); 

            // DaySets einblenden
            if ($sensorSet) {
            
                //$this->linkVar($this->searchObjectByName("DaySets"), "DaySets-Auswahl", $prnt, "|AFTER|" . $this->InstanceID, true);
                $this->linkFolderMobile($this->searchObjectByName("DaySets"), $name . " DaySets", $prnt);
                
            }

        }

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
           
            parent::ApplyChanges();

            $daysetActivated = $this->isSensorSet();
            $timeActivated = $this->ReadPropertyBoolean("ModeTime");

            //$onChangeEventName, $targetId, $function, $parent = null
            
            $this->CheckScripts();
 
            $this->checkSceneVars();

            $this->updateSceneVarProfile();

            $this->deleteOldDaysets();

            $this->CheckVariables();

            $this->checkSceneTimerVars();

            // $this->easyCreateOnChangeFunctionEvent("onChange Optionen", $this->searchObjectByName("Einstellungen"), "onOptionsChange", $this->searchObjectByName("Events"));
            $this->easyCreateOnChangeFunctionEvent("onChange Szene", $this->searchObjectByName("Szene"), "onSzenenChange", $this->searchObjectByName("Events"));

            if ($daysetActivated) {

                 $this->easyCreateOnChangeFunctionEvent("onChange Sensor", $this->ReadPropertyInteger("Sensor"), "onSensorChange", $this->searchObjectByName("Events"));

            }

            if (!$timeActivated) {

                $this->deleteObject($this->searchObjectByName("Timer Status"));

            }

            $this->addProfile($this->searchObjectByName("Szene"), $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);

            $this->deleteUnusedVars();

            $this->setTargetsOnChangeEvent();

            $this->deleteUnusedTargetOnChangeEvents();

            $daysetActivated = $this->isSensorSet();
            $daysetSensor = $this->ReadPropertyInteger("Sensor");

            // Experimental
 
            if ($daysetActivated) {

                $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1"));

                $this->easyCreateOnChangeFunctionEvent("onChange Automatik", $this->searchObjectByName("Automatik"), "onAutomatikChange", $this->searchObjectByName("Events"));
                $this->easyCreateOnChangeFunctionEvent("onChange Sperre", $this->searchObjectByName("Sperre"), "onSperreChange", $this->searchObjectByName("Events"));

                $daysets = $this->checkFolder("DaySets", null, 7);

                $this->updateSceneVarProfile();

                $profName = $this->getVariableProfileByVariable($daysetSensor);

                if ($profName != null) {

                    $assocs = $this->getProfileAssociations($profName);

                    if ($assocs != null) {

                        $counter = 1;

                        foreach ($assocs as $assoc) {

                            if (!$this->doesExist($this->searchObjectByName($assoc['Name'],$this->searchObjectByName("DaySets")))) {

                                $newVar = $this->checkInteger($assoc['Name'], false, $this->searchObjectByName("DaySets"), $counter, -1);
                                $this->addProfile($newVar, $this->prefix . ".DaysetScenes." . $this->InstanceID, true);
                                $counter = $counter + 1;

                            }

                        }

                    }

                }
 
                $this->setIcon($switches[0], "Power");
                $this->setIcon($switches[1], "Power");

                $this->addSwitch($switches[0]);

                $this->activateVariableLogging($switches[0]);
                $this->activateVariableLogging($switches[1]);

                // if (!$this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets anzeigen") && !$this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets verstecken")) {

                //     $this->addAssociations($this->prefix . ".Options" . $this->InstanceID, array("DaySets anzeigen" => 3));
                //     $this->addProfile($this->searchObjectByName("Einstellungen"), $this->prefix . ".Options" . $this->InstanceID);

                // }

            } else {

                $prnt = IPS_GetParent($this->InstanceID);

                $this->deleteObject($this->searchObjectByName("DaySets"));

                $this->deleteObject($this->searchObjectByName("DaySets-Auswahl", $prnt));

                // $this->removeAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets anzeigen");
                // $this->removeAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets verstecken");

            }

            $this->checkCurrentScene();
            $this->refreshSceneHashList();

        }

        public function Destroy () {

            parent::Destroy();


            //IPS_DeleteVariableProfile($this->prefix . ".Options" . $this->InstanceID);
            IPS_DeleteVariableProfile($this->prefix . ".ScenesVarProfile." . $this->InstanceID);

            if (IPS_VariableProfileExists($this->prefix . ".DaysetScenes." . $this->InstanceID)) {

                IPS_DeleteVariableProfile($this->prefix . ".DaysetScenes." . $this->InstanceID);

            }

        }

        public function onSperreChange () {

            $sperreVar = $this->searchObjectByName("Sperre");
            $sperreVal = GetValue($sperreVar);

            if ($sperreVal == false) {

                $this->onSensorChangeInternal();

            }

        }

        public function CheckVariables () {

            //$optionen = $this->checkInteger("Einstellungen", false, null, 99, -1);
            $sceneVar = $this->checkInteger("Szene", false, null, 1, 0);

            $targets = $this->checkFolder("Targets", null, 4);
            $events = $this->checkFolder("Events", null, 5);
            $sceneData = $this->checkFolder("SceneData", null, 6);

            $sceneCheckBlock = $this->checkBoolean("Block");
            $this->hide($sceneCheckBlock);

            $daysetActivated = $this->isSensorSet();
            $daysetSensor = $this->ReadPropertyInteger("Sensor");
            $timeIsActivated = $this->ReadPropertyBoolean("ModeTime");

            if ($timeIsActivated) {

                $status = $this->checkBoolean("Status", true, "", 2);
                $lastScene = $this->checkString("LastScene", false, $this->InstanceID, 5, null);

                $this->setIcon($status, "Power");

                $this->easyCreateOnChangeFunctionEvent("onChange Status", $status, "onStatusChange", $this->searchObjectByName("Events"));


                $this->hide($lastScene);
                

            } else {

                $this->deleteObject($this->searchObjectByName("Status"));

                $this->deleteObject($this->searchObjectByName("onChange Status", $this->searchObjectByName("Events")));

            }

            //$name, $setProfile = false, $position = "", $index = 0, $defaultValue = null, $istAbstand = false
            //$this->checkString("", false, $this->InstanceID, "|AFTER|" . $sceneVar, null, true);

            // $this->addProfile($optionen, $this->prefix . ".Options" . $this->InstanceID);
            $this->addProfile($sceneVar, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, false);
            $this->addVariableCustomAction($sceneVar, $this->searchObjectByName("SetScene"));

            // $this->setIcon($optionen, "Gear");
            $this->setIcon($sceneVar, "Rocket");

            // $this->addSetValue($optionen);


        }
    
        public function RegisterProperties () {
    
            $this->RegisterPropertyBoolean("ModeDaySet", true);
            $this->RegisterPropertyString("Names", "[{\"Name\":\"Aus\",\"ID\":0}]");
            $this->RegisterPropertyBoolean("ModeTime", false);
            $this->RegisterPropertyBoolean("Loop", false);
            $this->RegisterPropertyInteger("Sensor", null);
            $this->RegisterPropertyInteger("TimeVarMode", 0);

        }
    
        public function CheckScripts () {
    
            // Hier werden alle nötigen Scripts erstellt (SetValue wird automatisch erstellt)
            $setScene = $this->checkScript("SetScene", "<?php SetValue(\$IPS_VARIABLE, \$IPS_VALUE);" . $this->prefix . "_executeSceneById($this->InstanceID, \$IPS_VALUE); ?>", false, true);

            $timeIsActivated = $this->ReadPropertyBoolean("ModeTime");

            if ($timeIsActivated) {

                $nextElement = $this->checkScript("nextElement", $this->prefix . "_nextElement", 1001); 
                $this->hide($nextElement);

            } else {

                $this->deleteObject($this->searchObjectByName("nextElement"));

            }
    
        }

        public function CheckProfiles () {

            //checkVariableProfile ($name, $type, $min = 0, $max = 100, $steps = 1, $associations = null) {
            // $this->checkVariableProfile($this->prefix . ".Options" . $this->InstanceID, $this->varTypeByName("int"), 0, 3, 0, array("Zeige Einstellungen" => 0, "Modul einklappen" => 1, "Start" => 2));
            //$this->checkVariableProfile($this->prefix . ".StartStop." . $this->InstanceID, 1, 0, 1, 0, array("Start" => 1));
            $this->checkVariableProfile($this->prefix . ".SceneOptions", $this->varTypeByName("int"), 0, 1, 0, array("Speichern" => 0, "Ausführen" => 1));
            $this->checkVariableProfile($this->prefix . ".SceneTimerVarSek", $this->varTypeByName("int"), 0, 3600, 1, null, "", " s");
            $this->checkVariableProfile($this->prefix . ".SceneTimerVarMin", $this->varTypeByName("int"), 0, 600, 1, null, "", " min");

        }

        #                            #
        #   Modulspez. Funktionen    #
        #                            #

        protected function deleteOldDaysets () {

            if ($this->isSensorSet()) {

                $oldSensor = $this->eventGetTriggerVariable($this->searchObjectByName("onChange Sensor", $this->searchObjectByName("Events")));
                ////echo "OldSensor: " . $oldSensor;
                $sensor = $this->ReadPropertyInteger("Sensor");

                if ($sensor != null) { 

                    if ($oldSensor != $sensor) {

                        ////echo "Delete old DaySets ... "; 
                        $this->deleteAllChildren($this->searchObjectByName("DaySets"));

                    }

                } 

            }

        }

        protected function setTargetsOnChangeEvent () {

            $targets = $this->searchObjectByName("Targets");

            if (IPS_HasChildren($targets)) {

                $targets = IPS_GetObject($targets);

                foreach ($targets['ChildrenIDs'] as $child) {

                    if ($this->isLink($child)) {

                        $child = IPS_GetLink($child);
                        $childTarget = $child['TargetID'];

                        if (!$this->doesExist($this->searchObjectByName("onChangeSensor " . $childTarget . " " . $this->InstanceID))) {

                            $this->easyCreateOnChangeFunctionEvent("onChangeSensor " . $childTarget . " " . $this->InstanceID, $childTarget, "<?php " . $this->prefix . "_targetSensorChange(" . $this->InstanceID . ");" . " ?>", $this->searchObjectByName("Events"), false);

                        }

                    }

                }

            }

        }

        protected function deleteUnusedTargetOnChangeEvents () {

            $events = $this->searchObjectByName("Events");
            $targets = $this->searchObjectByName("Targets");

            $targets = IPS_GetObject($targets);

            if (IPS_HasChildren($events)) {

                $children = $this->getAllObjectsContainsString("onChangeSensor", $this->searchObjectByName("Events"));

                foreach ($children as $child) {

                    $child = IPS_GetEvent($child);
                    $isUsed = false;

                    foreach ($targets['ChildrenIDs'] as $target) {

                        if ($this->isLink($target)) {

                            $target = IPS_GetLink($target);

                            if ($target['TargetID'] == $child['TriggerVariableID']) {

                                $isUsed = true;

                            }

                        }

                    }

                    if (!$isUsed) {

                        $this->deleteObject($child['EventID']);

                    }

                }

            }

        }

        public function nextElement () {

            $allScenes = $this->getAllScenesSorted();

            $lastScene = GetValue($this->searchObjectByName("LastScene"));

            if ($lastScene == null) {

                SetValue($this->searchObjectByName("LastScene"), $allScenes[1]);
                SetValue($this->searchObjectByName($allScenes[1]), 1);

                $lc = GetValue($this->searchObjectByName("LastScene"));

                IPS_SetScriptTimer($this->searchObjectByName("nextElement"), $this->getTimerLengthBySceneName($allScenes[1]));

                $this->setIcon($this->getFirstChildFrom($this->searchObjectByName("nextElement")), "Clock");

                $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("nextElement")), "Timer Status", $this->InstanceID, "|AFTER|" . $this->searchObjectByName($lc . " Timer"), true);

            } else {

                $nextElement = $this->getElementAfterInArray($lastScene, $allScenes);

                if ($nextElement != "last") {

                    SetValue($this->searchObjectByName("LastScene"), $nextElement);
                    SetValue($this->searchObjectByName($nextElement), 1);

                    $lc = GetValue($this->searchObjectByName("LastScene"));

                    IPS_SetScriptTimer($this->searchObjectByName("nextElement"), $this->getTimerLengthBySceneName($nextElement));
                    $this->setPosition($this->searchObjectByName("Timer Status"), "|AFTER|" . $this->searchObjectByName($lc . " Timer"));

                } else {

                    if ($this->ReadPropertyBoolean("Loop")) {

                        SetValue($this->searchObjectByName("LastScene"), $allScenes[1]);
                        SetValue($this->searchObjectByName($allScenes[1]), 1);

                        $fc = $this->searchObjectByName($allScenes[1] . " Timer");

                        IPS_SetScriptTimer($this->searchObjectByName("nextElement"), $this->getTimerLengthBySceneName($allScenes[1]));

                        $this->setPosition($this->searchObjectByName("Timer Status"), "|AFTER|" . $fc);

                    } else {

                        IPS_DeleteLink($this->searchObjectByName("Timer Status"));

                        SetValue($this->searchObjectByName("LastScene"), null);
                        //SetValue($this->searchObjectByName($allScenes[0]), 1);
                        SetValue($this->searchObjectByName("Szene"), 0);

                        // if ($this->profileHasAssociation($this->prefix . ".StartStop." . $this->InstanceID, "Stop")) {

                        //     $this->changeAssociations($this->prefix . ".StartStop." . $this->InstanceID, array("Stop" => "Start"));
                        //     $this->addProfile($this->searchObjectByName("Einstellungen"), $this->prefix . ".StartStop." . $this->InstanceID);

                        // }

                        IPS_SetScriptTimer($this->searchObjectByName("nextElement"), 0);

                        SetValue($this->searchObjectByName("Status"), false);

                    }

                }

            }

        }

        public function getAllScenesSorted () {

            $scenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");
            usort($scenes, function($a, $b) {

                $sc1 = IPS_GetObject($this->searchObjectByName($a));
                $sc2 = IPS_GetObject($this->searchObjectByName($b));

                return $sc1['ObjectPosition'] > $sc2['ObjectPosition'];

            }); 

            return $scenes;

        }

        protected function getTimerLengthBySceneName ($sceneName) {

            $timeMode = $this->ReadPropertyInteger("TimeVarMode");
            $timer = GetValue($this->searchObjectByName($sceneName . " Timer"));

            if ($timeMode == 1) {

                $timer = $timer * 60;

            }

            return $timer;

        }

        protected function checkSceneVars () {

            $own = IPS_GetObject($this->InstanceID);

            $scenes = $this->ReadPropertyString("Names");

            $scenes = json_decode($scenes);

            //print_r($scenes);

            $existingScenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");

            $sceneNames = null;

            if ($this->arrayNotEmpty($scenes)) {

                $counter = 0;

                foreach ($scenes as $scene) {

                    $doesexist = false;

                    if ($existingScenes != null) {

                        if ($this->arrayNotEmpty($existingScenes)) {

                            foreach ($existingScenes as $escene) {
    
                                if ($escene == $scene->Name) {
    
                                    $doesexist = true;
    
                                }
    
                            }
    
                        }

                    }

                    if (!$doesexist) {

                        $newPos = $this->getHighestPosition() + 1;
                        $newInt = $this->checkInteger($scene->Name, false, $this->InstanceID, $newPos, -1);
                        $newSceneData = $this->checkString($scene->Name . " SceneData", false, $this->searchObjectByName("SceneData"), 0, "");
                        $this->addSetValue($newInt);
                        $this->setIcon($newInt, "Rocket");
                        $this->addProfile($newInt, $this->prefix . ".SceneOptions");



                        IPS_SetPosition($newSceneData, $newPos);

                        $this->easyCreateOnChangeFunctionEvent("onChange " . $newInt, $newInt, "onSceneVarChange", $this->searchObjectByName("Events"));

                        if ($scene == $scenes[0]) {

                            $this->hide($newInt);

                        }

                    }

                }

            }

        }

        protected function checkSceneTimerVars () {

            $modeActivated = $this->ReadPropertyBoolean("ModeTime");

            if ($modeActivated) {

                $allTimerVars = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVarSek");
                $allTimerVars2 = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVarMin");
                $allTimerVars = $this->combineArrays($allTimerVars2, $allTimerVars);
                $allSceneVars = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");

                //print_r($allSceneVars);

                if ($allSceneVars == null) {
                    return;
                }

                foreach ($allSceneVars as $sceneVar) {

                    $doesExist = false;

                    $sceneVarObj = IPS_GetObject($this->searchObjectByName($sceneVar));

                    if ($this->arrayNotEmpty($allTimerVars)) {

                        foreach ($allTimerVars as $timerVar) {

                            $timerVarObj = IPS_GetObject($this->searchObjectByName($timerVar));

                            if ($timerVarObj['ObjectName'] == $sceneVarObj['ObjectName'] . " Timer") {

                                $doesExist = true;

                            }

                        }

                    }

                    if (!$doesExist && ($sceneVar != $allSceneVars[0])) {

                        $checkTimer = $this->checkInteger($sceneVarObj['ObjectName'] . " Timer", false, "", "|AFTER|" . $this->searchObjectByname($sceneVar), 10);
                        $this->setIcon($checkTimer, "Clock");

                        $timerVarMode = $this->ReadPropertyInteger("TimeVarMode");

                        if ($timerVarMode == 0) {

                            $this->addProfile($checkTimer, $this->prefix . ".SceneTimerVarSek");

                        } else if ($timerVarMode == 1) {

                            $this->addProfile($checkTimer, $this->prefix . ".SceneTimerVarMin");

                        }

                        $this->addSetValue($checkTimer);

                    } else if ($this->doesExist($this->searchObjectByName($sceneVarObj['ObjectName'] . " Timer"))) {

                        $timerVarMode = $this->ReadPropertyInteger("TimeVarMode");

                        $timer = $this->searchObjectByName($sceneVarObj['ObjectName'] . " Timer");

                        if ($timerVarMode == 0) {

                            $this->addProfile($timer, $this->prefix . ".SceneTimerVarSek");

                        } else if ($timerVarMode == 1) {

                            $this->addProfile($timer, $this->prefix . ".SceneTimerVarMin");

                        }

                    }

                }
                

            }

        }

        protected function deleteUnusedVars () {

            $existingScenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");
            $existingSceneTimers = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVarSek");
            $existingSceneTimers2 = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVarMin");
            $existingSceneTimers = $this->combineArrays($existingSceneTimers, $existingSceneTimers2);
            $timerIsEnabled = $this->ReadPropertyBoolean("ModeTime");
            $daysetActivated = $this->isSensorSet();

            $sceneNames = $this->getAllSceneNames();

            $sensor = $this->ReadPropertyInteger("Sensor");

            // if ($sensor != $lastSensor) {

            //     $this->deleteAllChildren($this->searchObjectByName("DaySets"));

            // }

            if (!$daysetActivated) {

                $this->deleteObject($this->searchObjectByRealName("onChange Automatik"), $this->searchObjectByName("Events"));
                $this->deleteObject($this->searchObjectByRealName("onChange Sperre"), $this->searchObjectByName("Events"));
                $this->deleteObject($this->AutomatikVar);
                $this->deleteObject($this->SperreVar);

                $this->deleteObject($this->searchObjectByName("onChange Sensor", $this->searchObjectByName("Events")));

            } else {

                ////echo "Dayset activated";
                $oldSensor = $this->eventGetTriggerVariable($this->searchObjectByName("onChange Sensor", $this->searchObjectByName("DaySets")));
                $sensor = $this->ReadPropertyInteger("Sensor");

                if ($oldSensor != $sensor) {
                    $this->deleteObject($this->searchObjectByName("onChange Sensor", $this->searchObjectByName("Events")));
                    $this->easyCreateOnChangeFunctionEvent("onChange Sensor", $this->ReadPropertyInteger("Sensor"), "onSensorChange", $this->searchObjectByName("Events"));
                }

            }


            if ($existingScenes == null) {
                return;
            }

            if ($timerIsEnabled != true) {

                if ($existingSceneTimers != null) {

                    if ($this->arrayNotEmpty($existingSceneTimers)) {

                        foreach ($existingSceneTimers as $timerVar) {

                            $timerVar = $this->searchObjectByName($timerVar);

                            $this->deleteObject($timerVar);

                        }

                    }

                }

            }

            if ($sceneNames == null || $sceneNames == "") {

                foreach ($existingScenes as $eScene) {

                    $eSceneVarId = $this->searchObjectByName($eScene);

                    // Delete Object
                    $this->deleteObject($this->searchObjectByName($eScene));

                    // Delete Timer if existing
                    if ($this->doesExist($this->searchObjectByName($eScene . " Timer"))) {

                        $this->deleteObject($this->searchObjectByName($eScene . " Timer"));

                    }

                    // Delete Event if existing
                    if ($this->doesExist($this->searchObjectByName("onChange " . $eSceneVarId, $this->searchObjectByName("Events")))) {

                        $this->deleteObject($this->searchObjectByName("onChange " . $eSceneVarId, $this->searchObjectByName("Events")));

                    }


                    // Delete SceneData if existing
                    if ($this->doesExist($this->searchObjectByName($eScene . " SceneData", $this->searchObjectByName("SceneData")))) {

                        $this->deleteObject($this->searchObjectByName($eScene . " SceneData", $this->searchObjectByName("SceneData")));

                    }

                }

            }

            $completeDelete = false;

            if ($sceneNames == null) {
                $completeDelete = true;
                $sceneNames = array();
            }

            foreach ($existingScenes as $eScene) {

                if (!in_array($eScene, $sceneNames) || $completeDelete) {

                    if ($this->doesExist($this->searchObjectByName($eScene))) {

                        $eSceneVarId = $this->searchObjectByName($eScene);

                        // Delete Object
                        $this->deleteObject($this->searchObjectByName($eScene));

                        // Delete Timer if existing
                        if ($this->doesExist($this->searchObjectByName($eScene . " Timer"))) {

                            $this->deleteObject($this->searchObjectByName($eScene . " Timer"));

                        }

                        // Delete Event if existing
                        if ($this->doesExist($this->searchObjectByName("onChange " . $eSceneVarId, $this->searchObjectByName("Events")))) {

                            $this->deleteObject($this->searchObjectByName("onChange " . $eSceneVarId, $this->searchObjectByName("Events")));

                        }

                        // Delete SceneData if existing
                        if ($this->doesExist($this->searchObjectByName($eScene . " SceneData", $this->searchObjectByName("SceneData")))) {

                            $this->deleteObject($this->searchObjectByName($eScene . " SceneData", $this->searchObjectByName("SceneData")));

                        }

                    }

                }

            }

        }

        protected function isSensorSet () {

            $sens = $this->ReadPropertyInteger("Sensor");

            if ($sens != null && $sens != 0) {

                return true;

            } else {

                return false;

            }

        }

        protected function getAllSceneNames () {

            $scenes = json_decode($this->ReadPropertyString("Names"));

            $ary = null;

            if ($this->arrayNotEmpty($scenes)) {

                foreach ($scenes as $scene) {

                    $ary[] = $scene->Name;

                }

            }

            return $ary;

        }

        protected function updateSceneVarProfile () {

            $scenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");

            $scenesByList = $this->ReadPropertyString("Names");
            $scenesByList = json_decode($scenesByList);

            $assocs = null;

            $counter = 0;

            if ($this->arrayNotEmpty($scenes)) {

                foreach ($scenesByList as $scene) {

                    // $scene = IPS_GetObject($this->searchObjectByName($scene));

                    $sceneName = $scene->Name;

                    $assocs[$sceneName] = $counter;
                    
                    $counter = $counter + 1;

                }

                //print_r($scenes);

                if (IPS_VariableProfileExists($this->prefix . ".DaysetScenes." . $this->InstanceID)) {
                    IPS_DeleteVariableProfile($this->prefix . ".DaysetScenes." . $this->InstanceID);
                }

                $this->createDynamicProfile($this->prefix . ".ScenesVarProfile." . $this->InstanceID, $assocs);
                $this->cloneVariableProfile($this->prefix . ".ScenesVarProfile." . $this->InstanceID, $this->prefix . ".DaysetScenes." . $this->InstanceID);
                $this->addAssociations($this->prefix . ".DaysetScenes." . $this->InstanceID, array("—" => -1));
                $this->addAssociations($this->prefix . ".ScenesVarProfile." . $this->InstanceID, array("Individuell" => 999));

            } else {

                $this->createDynamicProfile($this->prefix . ".ScenesVarProfile." . $this->InstanceID, array("Individuell" => 999));

            }

            //$this->createDynamicProfile();

        }

        protected function getSceneHashList () {

            if ($this->doesExist($this->searchObjectByName("SceneHashList"))) {

                $shl = GetValue($this->searchObjectByName("SceneHashList"));

                $shl = json_decode($shl);

            } else {

                $shl = null;

            }

            return $shl;

        }

        protected function refreshSceneHashList () {

            if (!$this->doesExist($this->searchObjectByName("SceneHashList"))) {

                $shl = $this->checkString("SceneHashList");
                $this->hide($shl);

            }

            $sceneDataVar = $this->searchObjectByName("SceneData");

            $allScenes = $this->getAllScenesSorted();

            $ary = array();

            if (IPS_HasChildren($sceneDataVar)) {

                // Null - Offen - Aus Szene ==> entspricht md5("")
                $ary[0] = "d41d8cd98f00b204e9800998ecf8427e";

                foreach ($allScenes as $scene) {

                    if ($scene != $allScenes[0]) {

                        $sceneVal = $this->searchObjectByName($scene . " SceneData", $sceneDataVar);
                        $ary[] = md5($sceneVal);

                    }
                    //$childVal = md5($childVal);
                    //if (!in_array(md5($childVal), $ary)) {
                    //}

                }

            }

            SetValue($this->searchObjectByName("SceneHashList"), json_encode($ary));

        }

        ##                 ##
        ## OnChange Events ##
        ##                 ##
        
        public function onSensorChange () {

            $senderVar = $_IPS['VARIABLE'];
            $senderVal = GetValue($senderVar);
            $automatik = GetValue($this->AutomatikVar);
            $sperre = GetValue($this->SperreVar);
            $sensor = $this->ReadPropertyInteger("Sensor");
            $sensorProfile = $this->getVariableProfileByVariable($sensor);
            $sensorVal = GetValue($sensor);

            if ($automatik && !$sperre) {

                $dsName = $this->getAssociationTextByValue($sensorProfile, $senderVal);
                ////echo "dsName: " . $dsName;
                $dsObj = $this->searchObjectByName($dsName, $this->searchObjectByName("DaySets"));

                $dsVal = GetValue($dsObj);



                    if ($dsVal != -1) {

                        SetValue($this->searchObjectByName("Szene"), $dsVal);

                    }

                

            }
            

        }

        public function onSensorChangeInternal () {

            $senderVar = $this->ReadPropertyInteger("Sensor");
            $senderVal = GetValue($senderVar);
            $automatik = GetValue($this->AutomatikVar);
            $sperre = GetValue($this->SperreVar);
            $sensor = $this->ReadPropertyInteger("Sensor");
            $sensorProfile = $this->getVariableProfileByVariable($sensor);
            $sensorVal = GetValue($sensor);

            if ($automatik && !$sperre) {

                $dsName = $this->getAssociationTextByValue($sensorProfile, $senderVal);
                ////echo "dsName: " . $dsName;
                $dsObj = $this->searchObjectByName($dsName, $this->searchObjectByName("DaySets"));

                $dsVal = GetValue($dsObj);



                    if ($dsVal != -1) {

                        SetValue($this->searchObjectByName("Szene"), $dsVal);

                    }

                

            }

        }

        public function onAutomatikChange() {

            $automatik = GetValue($this->searchObjectByName("Automatik"));

            // Wenn Automatik auf true
            if ($automatik) {

                $this->onSensorChangeInternal();

            } else {
            // Wenn Automatik auf false, Timer Löschen (Funktion prüft autom. ob Element existiert)!

                $this->deleteObject($this->getFirstChildFrom($this->searchObjectByName("nextElement")));

            }

        }

        public function onSceneVarChange () {

            $senderVar = $_IPS['VARIABLE'];
            $senderObj = IPS_GetObject($senderVar);
            $senderVal = GetValue($senderVar);
            $senderName = $senderObj['ObjectName'];
            $targets = IPS_GetObject($this->searchObjectByName("Targets"));

            if ($_IPS['OLDVALUE'] == $senderVal) {
                return;
            }

            // Wenn Speichern
            if ($senderVal == 0) {
            
                $sceneDataName = $senderName . " SceneData";
                $sceneDataVar = $this->searchObjectByName($sceneDataName, $this->searchObjectByName("SceneData"));
                $sceneDataVal = GetValue($sceneDataVar);
                
                //if ($sceneDataVal != null && $sceneDataVal != "") {

                    $states = array();

                    if ($this->arrayNotEmpty($targets['ChildrenIDs']))  {

                        foreach ($targets['ChildrenIDs'] as $child) {

                            $child = IPS_GetObject($child);

                            if ($child['ObjectType'] == $this->objectTypeByName("Link")) {

                                $child = IPS_GetLink($child['ObjectID']);

                                $tg = $child['TargetID'];

                                $states[$tg] = GetValue($tg);

                            }

                        }

                        if (!in_array(json_encode($states), $this->getSceneHashList())) {

                            SetValue($sceneDataVar, json_encode($states));

                            $this->refreshSceneHashList();

                        } else {

                            $this->sendWebfrontNotification("Szene existiert bereits", "Diese Szene existiert bereits, doppelte Szenen können zu Fehlern führen!", "Bulb", 5);                            

                        }

                    }

                //}
                
                $this->targetSensorChange();

            } else if ($senderVal == 1) {

                // Wenn Ausführen
                $sceneDataName = $senderName . " SceneData";
                $sceneDataVar = $this->searchObjectByName($sceneDataName, $this->searchObjectByName("SceneData"));
                $sceneDataVal = GetValue($sceneDataVar);

                $allScenes = $this->getAllScenesSorted();
                
                if ($sceneDataVal != null && $sceneDataVal != "") {

                    $json = json_decode($sceneDataVal);

                    foreach ($json as $id => $val) {

                        $oldVal = GetValue($id);

                        if ($oldVal != $val) {

                            $prnt = IPS_GetParent($id);

                            if ($this->isInstance($prnt)) {

                                $prnt = IPS_GetInstance($prnt);

                                if ($prnt['ModuleInfo']['ModuleName'] == "SymconSzenenV2") {

                                    ////echo "Szenenmodul " . ".." . " schalten ...";
                                    Sleep(1);
                                    SetValue($id, $val);

                                } else {
                                    $this->setDevice($id, $val);
                                }

                            } else {
                                $this->setDevice($id, $val);
                            }

                        }

                    }

                    $this->setVariableTemp($this->searchObjectByName("Block"), true, 15);

                } else {

                    // if ($senderName == $allScenes[0]) {

                    //     $this->setAllInLinkList($this->searchObjectByName("Targets"), false);

                    // } else {

                        $this->sendWebfrontNotification("Keine Szenen Daten", "Es konnten keine Szenen Daten gefunden werden!", "Bulb", 5);

                    // }

                }

            }

            SetValue($senderVar, -1);

        }

        public function onSzenenChange() {

            $sender = $_IPS['VARIABLE'];
            $senderVal = GetValue($sender);
            $alleScenes = $this->getAllScenesSorted();

            // if ($_IPS['OLDVALUE'] == $senderVal) {
            //     return;
            // }

            $this->executeSceneById($senderVal);

        }

        public function onStatusChange () {

            $var = $_IPS['VARIABLE'];
            $val = GetValue($var);

            // Start / Stop Zeitschaltung
            if ($val == true) {

                $this->nextElement();

                return;

            } else {

                $allScenes = $this->getAllScenesSorted();

                //SetValue($this->searchObjectByName($allScenes[0]), 1);
                SetValue($this->searchObjectByName("Szene"), 0);

                $this->deleteObject($this->searchObjectByName("Timer Status"));
                $this->deleteObject($this->getFirstChildFrom($this->searchObjectByName("nextElement")));

                SetValue($this->searchObjectByName("LastScene"), "");

            }

        }

        public function targetSensorChange () {
                
                //if ($sceneDataVal != null && $sceneDataVal != "") {

                $states = array();
                $targets = IPS_GetObject($this->searchObjectByName("Targets"));
                $send = $_IPS['VARIABLE'];
                $send = GetValue($send);
                $block = $this->searchObjectByName("Block");
                $blockVal = GetValue($block);

                if ($blockVal) {
                    return;
                }

                if (!$this->arrayNotEmpty($targets['ChildrenIDs'])) {

                    return;

                }

                if ($_IPS['OLDVALUE'] == $send) {

                    return;

                }

                if ($this->arrayNotEmpty($targets['ChildrenIDs']))  {

                    foreach ($targets['ChildrenIDs'] as $child) {

                            $child = IPS_GetObject($child);

                            if ($child['ObjectType'] == $this->objectTypeByName("Link")) {

                                $child = IPS_GetLink($child['ObjectID']);

                                $tg = $child['TargetID'];

                                $states[$tg] = GetValue($tg);

                            }

                        }

                        if (!in_array(md5(json_encode($states)), $this->getSceneHashList())) {

                            $found = false;

                            if (!$found) {

                                $obj = IPS_GetObject($this->searchObjectByName("Targets"));

                                $anyTrue = false;

                                foreach ($obj['ChildrenIDs'] as $child) {

                                    if ($this->isLink($child)) {

                                        $child = IPS_GetLink($child);
                                        $childVal = GetValue($child['TargetID']);

                                        if ($childVal == true) {

                                            $anyTrue = true;

                                        }

                                    }

                                }

                                if (!$anyTrue) {

                                    SetValue($this->searchObjectByName("Szene"), 0);

                                } else {

                                    SetValue($this->searchObjectByName("Szene"), 999);

                                }

                            }

                        } else {

                            foreach ($this->getSceneHashList() as $kkey => $kval) {

                                if ($kval == md5(json_encode($states))) {

                                    //$found = true;
                                    SetValue($this->searchObjectByName("Szene"), $kkey);

                                }

                            }

                        }
                        ////echo md5(json_encode($states));

                    }

                //}

        }

        protected function checkCurrentScene () {

            $states = array();
            $targets = IPS_GetObject($this->searchObjectByName("Targets"));

            if (!$this->arrayNotEmpty($targets['ChildrenIDs'])) {

                return;

            }

            if (!$this->arrayNotEmpty($this->getSceneHashList())) {

                return;

            }

            if ($this->arrayNotEmpty($targets['ChildrenIDs']))  {

                foreach ($targets['ChildrenIDs'] as $child) {

                        $child = IPS_GetObject($child);

                        if ($child['ObjectType'] == $this->objectTypeByName("Link")) {

                            $child = IPS_GetLink($child['ObjectID']);

                            $tg = $child['TargetID'];

                            $states[$tg] = GetValue($tg);

                        }

                    }

                    if (!in_array(md5(json_encode($states)), $this->getSceneHashList())) {

                        $found = false;

                        if (!$found) {

                            $obj = IPS_GetObject($this->searchObjectByName("Targets"));

                            $anyTrue = false;

                            foreach ($obj['ChildrenIDs'] as $child) {

                                if ($this->isLink($child)) {

                                    $child = IPS_GetLink($child);
                                    $childVal = GetValue($child['TargetID']);

                                    if ($childVal == true) {

                                        $anyTrue = true;

                                    }

                                }

                            }

                            if (!$anyTrue) {

                                SetValue($this->searchObjectByName("Szene"), 0);

                            } else {

                                SetValue($this->searchObjectByName("Szene"), 999);

                            }

                        }

                    } else {

                        foreach ($this->getSceneHashList() as $kkey => $kval) {

                            if ($kval == md5(json_encode($states))) {

                                //$found = true;
                                SetValue($this->searchObjectByName("Szene"), $kkey);

                            }

                        }

                    }
                    ////echo md5(json_encode($states));

                }

        }

        public function executeSceneById ($id) {

            $targets = $this->searchObjectByName("Targets");

            if ($id == 0) {

                $this->setAllInLinkList($targets, false);
                $this->setVariableTemp($this->searchObjectByName("Block"), true, 15);
                return;

            }

            if ($id == 999) {
                return;
            }

            
            $sceneName = $this->getAssociationTextByValue($this->prefix . ".ScenesVarProfile." . $this->InstanceID, $id);
            $sceneDataVal = GetValue($this->searchObjectByName($sceneName . " SceneData", $this->searchObjectByName("SceneData")));
            
            if ($sceneDataVal != null && $sceneDataVal != "") {

                $scene = json_decode($sceneDataVal);

                foreach ($scene as $sid => $sval) {

                    $currentVal = GetValue($sid);

                    if ($sval != $currentVal) {

                        $this->setDevice($sid, $sval);

                    }

                }

                $this->setVariableTemp($this->searchObjectByName("Block"), true, 15);

            } else {

                $this->sendWebfrontNotification("Keine Szenen Daten", "Es konnten keine Szenen Daten gefunden werden!", "Bulb", 5);

            }


        }


        //  Öffentliche Funktionen

        public function Start () {

            $timeIsActivated = $this->ReadPropertyBoolean("ModeTime");

            $started = GetValue($this->searchObjectByName("Status"));

            if ($timeIsActivated) {

                if ($started) {

                    // $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Start" => "Stop"));
                    // $this->addProfile($this->searchObjectByName("Einstellungen"), $this->prefix . ".Options" . $this->InstanceID);

                    $this->nextElement();

                    SetValue($this->searchObjectByName("Einstellungen"), -1);
                    return;

                } else {

                    //echo "Ist bereits gestartet!";

                }

            }

        }

        public function Stop () {

            $timeIsActivated = $this->ReadPropertyBoolean("ModeTime");

            $started = GetValue($this->searchObjectByName("Status"));

            if ($timeIsActivated) {

                if (!$started) {

                    $this->deleteObject($this->searchObjectByName("Timer Status"));
                    $this->deleteObject($this->getFirstChildFrom($this->searchObjectByName("nextElement")));

                    SetValue($this->searchObjectByName("LastScene"), "");

                    // $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Stop" => "Start"));
                    // $this->addProfile($this->searchObjectByName("Einstellungen"), $this->prefix . ".Options" . $this->InstanceID);

                } else {

                    //echo "Läuft nicht!";

                }

            }

        }



        // Analyse Tools

        public function GetSceneOverview () {

            $szenen = $this->getAllScenesSorted();

            $sceneDataFolder = $this->searchObjectByName("SceneData");

            //echo $sceneDataFolder;

            if (count($szenen) > 0) {

                foreach ($szenen as $szene) {
                    //echo $szene . "\n";

                    if ($szenen[0] != $szene) {

                        $szenenId = $this->searchObjectByName($szene . " SceneData", $sceneDataFolder);

                    //echo $szenenId;

                    $szene = json_decode(GetValue($szenenId));

                    echo IPS_GetName($szenenId) . ": \n";

                    foreach ($szene as $k => $val) {

                        echo "   " . $k . "(" . IPS_GetName($k) . ") ==>" . $val . "\n";

                    }

                    }

                }

            } else {

                echo "Keine Szenen vorhanden!";

            }

        }


    }


?>