<?

    require(__DIR__ . "\\pimodule.php");

    // Klassendefinition
    class SymconSzenenUltimateV2 extends PISymconModule {
 
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
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
           
            parent::ApplyChanges();

            //$onChangeEventName, $targetId, $function, $parent = null

            $this->CheckScripts();

            $this->checkSceneVars();

            $this->updateSceneVarProfile();

            $this->CheckVariables();

            $this->checkSceneTimerVars();

            $this->easyCreateOnChangeFunctionEvent("onChange Automatik", $this->searchObjectByName("Automatik"), "onAutomatikChange", $this->searchObjectByName("Events"));
            $this->easyCreateOnChangeFunctionEvent("onChange Optionen", $this->searchObjectByName("Optionen"), "onOptionsChange", $this->searchObjectByName("Events"));
            $this->easyCreateOnChangeFunctionEvent("onChange Szenen", $this->searchObjectByName("Szenen"), "onSzenenChange", $this->searchObjectByName("Events"));

            if ($this->ReadPropertyInteger("Sensor") != null && $this->ReadPropertyBoolean("ModeDaySet")) {

                $this->easyCreateOnChangeFunctionEvent("onChange Sensor", $this->ReadPropertyInteger("Sensor"), "onSensorChange", $this->searchObjectByName("Events"));

            }

            $this->addProfile($this->searchObjectByName("Szenen"), $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);

            $this->deleteUnusedVars();

        }

        public function Destroy () {

            parent::Destroy();

            IPS_DeleteVariableProfile($this->prefix . ".Options" . $this->InstanceID);

        }


        public function CheckVariables () {

            $optionen = $this->checkInteger("Optionen", false, null, 2, -1);
            $sceneVar = $this->checkInteger("Szenen", false, null, 3, 0);

            $targets = $this->checkFolder("Targets", null, 4);
            $events = $this->checkFolder("Events", null, 5);
            $sceneData = $this->checkFolder("SceneData", null, 6);


            $daysetActivated = $this->ReadPropertyBoolean("ModeDaySet");
            $timeIsActivated = $this->ReadPropertyBoolean("ModeTime");

            if ($timeIsActivated) {

                $lastScene = $this->checkString("LastScene", false, $this->InstanceID, 5, null);
                $this->hide($lastScene);

                if (!$this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Start") && !$this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Stop")) {

                    $this->addAssociations($this->prefix . ".Options" . $this->InstanceID, array("Start" => 2));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                }

            } else {

                $this->removeAssociation($this->prefix . ".Options" . $this->InstanceID, "Start");
                $this->removeAssociation($this->prefix . ".Options" . $this->InstanceID, "Stop");
                $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

            }

            if ($daysetActivated) {

                $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1"));

                $daysets = $this->checkFolder("DaySets", null, 7);

                $frueh = $this->checkInteger("Früh", false, $this->searchObjectByName("DaySets"), 1, 0);
                $morgen = $this->checkInteger("Morgen", false, $this->searchObjectByName("DaySets"), 2, 0);
                $tag = $this->checkInteger("Tag", false, $this->searchObjectByName("DaySets"), 3, 0);
                $daemmerung = $this->checkInteger("Daemmerung", false, $this->searchObjectByName("DaySets"), 4, 0);
                $abend = $this->checkInteger("Abend", false, $this->searchObjectByName("DaySets"), 5, 0);
                $nacht = $this->checkInteger("Nacht", false, $this->searchObjectByName("DaySets"), 6, 0);

                $this->addProfile($frueh, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);
                $this->addProfile($morgen, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);
                $this->addProfile($tag, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);
                $this->addProfile($daemmerung, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);
                $this->addProfile($abend, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);
                $this->addProfile($nacht, $this->prefix . ".ScenesVarProfile." . $this->InstanceID, true);

                $this->setIcon($switches[0], "Power");
                $this->setIcon($switches[1], "Power");

                if (!$this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets anzeigen") && !$this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets verstecken")) {

                    $this->addAssociations($this->prefix . ".Options" . $this->InstanceID, array("DaySets anzeigen" => 2));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                }

            } else {

                $this->deleteObject($this->searchObjectByName("DaySets"));

                $this->removeAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets anzeigen");
                $this->removeAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets verstecken");

            }

            //$name, $setProfile = false, $position = "", $index = 0, $defaultValue = null, $istAbstand = false
            //$this->checkString("", false, $this->InstanceID, "|AFTER|" . $sceneVar, null, true);

            $this->addProfile($optionen, $this->prefix . ".Options" . $this->InstanceID);
            $this->addProfile($sceneVar, $this->prefix . ".ScenesVarProfile." . $this->InstanceID);

            $this->setIcon($optionen, "Database");
            $this->setIcon($sceneVar, "Rocket");

            $this->addSetValue($optionen);


        }
    
        public function RegisterProperties () {
    
            $this->RegisterPropertyBoolean("ModeDaySet", true);
            $this->RegisterPropertyString("Names", "");
            $this->RegisterPropertyBoolean("ModeTime", false);
            $this->RegisterPropertyBoolean("Loop", false);
            $this->RegisterPropertyInteger("Sensor", null);
    
        }
    
        public function CheckScripts () {
    
            // Hier werden alle nötigen Scripts erstellt (SetValue wird automatisch erstellt)
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
            $this->checkVariableProfile($this->prefix . ".Options" . $this->InstanceID, $this->varTypeByName("int"), 0, 3, 0, array("Zeige Targets" => 0, "Modul verkleinern" => 1, "Start" => 2, "DaySets anzeigen" => 3));
            $this->checkVariableProfile($this->prefix . ".SceneOptions", $this->varTypeByName("int"), 0, 1, 0, array("Speichern" => 0, "Ausführen" => 1));
            $this->checkVariableProfile($this->prefix . ".SceneTimerVar", $this->varTypeByName("int"), 0, 3600, 1, null, "", " s");

        }

        #                            #
        #   Modulspez. Funktionen    #
        #                            #

        public function nextElement () {

            $allScenes = $this->getAllScenesSorted();

            $lastScene = GetValue($this->searchObjectByName("LastScene"));

            if ($lastScene == null) {

                SetValue($this->searchObjectByName("LastScene"), $allScenes[0]);
                SetValue($this->searchObjectByName($allScenes[0]), 1);

                $lc = GetValue($this->searchObjectByName("LastScene"));

                IPS_SetScriptTimer($this->searchObjectByName("nextElement"), $this->getTimerLengthBySceneName($allScenes[0]));
                //$target, $linkName = "Unnamed Link", $parent = null, $linkPosition = 0
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

                        SetValue($this->searchObjectByName("LastScene"), $allScenes[0]);
                        SetValue($this->searchObjectByName($allScenes[0]), 1);

                        $fc = GetValue($this->searchObjectByName($allScenes[0] . " Timer"));

                        IPS_SetScriptTimer($this->searchObjectByName("nextElement"), $this->getTimerLengthBySceneName($allScenes[0]));

                        $this->setPosition($this->searchObjectByName("Timer Status"), "|AFTER|" . $fc);

                    } else {

                        IPS_DeleteLink($this->searchObjectByName("Timer Status"));

                        SetValue($this->searchObjectByName("LastScene"), null);
                        $this->setAllInLinkList($this->searchObjectByName("Targets"), 0);

                        if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Stop")) {

                            $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Stop" => "Start"));
                            $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                        }

                        IPS_SetScriptTimer($this->searchObjectByName("nextElement"), 0);

                    }

                }

            }

        }

        public function getAllScenesSorted () {

            $scenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");
            return $scenes;

        }

        protected function getTimerLengthBySceneName ($sceneName) {

            $timer = GetValue($this->searchObjectByName($sceneName . " Timer"));
            return $timer;

        }

        protected function checkSceneVars () {

            $own = IPS_GetObject($this->InstanceID);

            $scenes = $this->ReadPropertyString("Names");

            $scenes = json_decode($scenes);

            //print_r($scenes);

            $existingScenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");

            $sceneNames = null;

            if (count($scenes) > 0) {

                foreach ($scenes as $scene) {

                    $doesexist = false;

                    if (count($existingScenes) > 0) {

                        foreach ($existingScenes as $escene) {

                            if ($escene == $scene->Name) {

                                $doesexist = true;

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

                        $this->easyCreateOnChangeFunctionEvent("onChange " . $newInt, $newInt, "onSceneVarChange", $this->searchObjectByName("Events"));


                    }

                }

            }

        }

        protected function checkSceneTimerVars () {

            $modeActivated = $this->ReadPropertyBoolean("ModeTime");

            if ($modeActivated) {

                $allTimerVars = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVar");
                $allSceneVars = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");

                //print_r($allSceneVars);

                if ($allSceneVars == null) {
                    return;
                }

                foreach ($allSceneVars as $sceneVar) {

                    $doesExist = false;

                    $sceneVarObj = IPS_GetObject($this->searchObjectByName($sceneVar));

                    if (count($allTimerVars) > 0) {

                        foreach ($allTimerVars as $timerVar) {

                            $timerVarObj = IPS_GetObject($this->searchObjectByName($timerVar));

                            if ($timerVarObj['ObjectName'] == $sceneVarObj['ObjectName'] . " Timer") {

                                $doesExist = true;

                            }

                        }

                    }

                    if (!$doesExist) {

                        $checkTimer = $this->checkInteger($sceneVarObj['ObjectName'] . " Timer", false, "", "|AFTER|" . $this->searchObjectByname($sceneVar), 10);
                        $this->setIcon($checkTimer, "Clock");
                        $this->addProfile($checkTimer, $this->prefix . ".SceneTimerVar");
                        $this->addSetValue($checkTimer);

                    }

                }
                

            }

        }

        protected function deleteUnusedVars () {

            $existingScenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");
            $existingSceneTimers = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVar");
            $timerIsEnabled = $this->ReadPropertyBoolean("ModeTime");
            $daysetActivated = $this->ReadPropertyBoolean("ModeDaySet");

            $sceneNames = $this->getAllSceneNames();

            if (!$daysetActivated) {

                $this->deleteObject($this->AutomatikVar);
                $this->deleteObject($this->SperreVar);
                $this->deleteObject($this->searchObjectByName("onChange Sensor", $this->searchObjectByName("Events")));

            }

            if ($existingScenes == null) {
                return;
            }

            if ($timerIsEnabled != true) {

                if ($existingSceneTimers != null) {

                    if (count($existingSceneTimers) > 0) {

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

            foreach ($existingScenes as $eScene) {

                if (!in_array($eScene, $sceneNames)) {

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

                    }

                }

            }

        }

        protected function getAllSceneNames () {

            $scenes = json_decode($this->ReadPropertyString("Names"));

            $ary = null;

            if (count($scenes) > 0) {

                foreach ($scenes as $scene) {

                    $ary[] = $scene->Name;

                }

            }

            return $ary;

        }

        protected function updateSceneVarProfile () {

            $scenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");

            $assocs = null;

            $counter = 0;

            if (count($scenes) > 0) {

                foreach ($scenes as $scene) {

                    $scene = IPS_GetObject($this->searchObjectByName($scene));

                    $sceneName = $scene['ObjectName'];

                    $assocs[$sceneName] = $counter;
                    
                    $counter = $counter + 1;

                }

                $this->createDynamicProfile($this->prefix . ".ScenesVarProfile." . $this->InstanceID, $assocs);

            }

            //$this->createDynamicProfile();

        }

        ##                 ##
        ## OnChange Events ##
        ##                 ##
        
        public function onSensorChange () {

            $senderVar = $_IPS['VARIABLE'];
            $senderVal = GetValue($senderVar);
            $automatik = GetValue($this->AutomatikVar);
            $sperre = GetValue($this->SperreVar);

            if ($automatik && !$sperre) {

                SetValue($this->searchObjectByName("Szenen"), $senderVal);

            }
            

        }

        public function onAutomatikChange () {

            $automatik = GetValue($this->AutomatikVar);

            // Wenn Automatik auf true
            if ($automatik) {



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

                    if (count($targets['ChildrenIDs']) > 0)  {

                        foreach ($targets['ChildrenIDs'] as $child) {

                            $child = IPS_GetObject($child);

                            if ($child['ObjectType'] == $this->objectTypeByName("Link")) {

                                $child = IPS_GetLink($child['ObjectID']);

                                $tg = $child['TargetID'];

                                $states[$tg] = GetValue($tg);

                            }

                        }

                        SetValue($sceneDataVar, json_encode($states));

                    }

                //}


            } else if ($senderVal == 1) {

                // Wenn Ausführen
                $sceneDataName = $senderName . " SceneData";
                $sceneDataVar = $this->searchObjectByName($sceneDataName, $this->searchObjectByName("SceneData"));
                $sceneDataVal = GetValue($sceneDataVar);
                
                if ($sceneDataVal != null && $sceneDataVal != "") {

                    $json = json_decode($sceneDataVal);

                    foreach ($json as $id => $val) {

                        $this->setDevice($id, $val);

                    }

                }

            }

            SetValue($senderVar, -1);

        }

        public function onSzenenChange() {

            $sender = $_IPS['VARIABLE'];
            $senderVal = GetValue($sender);

            if ($_IPS['OLDVALUE'] == $senderVal) {
                return;
            }

            $sceneName = $this->getAssociationTextByValue($this->prefix . ".ScenesVarProfile." . $this->InstanceID, $senderVal);
            $sceneDataVal = GetValue($this->searchObjectByName($sceneName . " SceneData", $this->searchObjectByName("SceneData")));
            
            if ($sceneDataVal != null && $sceneDataVal != "") {

                $sceneData = json_decode($sceneDataVal);

                foreach ($sceneData as $devId => $devVal) {

                    $this->setDevice($devId, $devVal);

                }

            }

        }

        public function onOptionsChange () {

            $optionsVal = GetValue($this->searchObjectByName("Optionen"));
            $prnt = IPS_GetParent($this->InstanceID);
            $own = IPS_GetObject($this->InstanceID);
            $scenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");
            $timers = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVar");

            if ($_IPS['OLDVALUE'] == $optionsVal) {
                return;
            }

            // Zeige Targets
            if ($optionsVal == 0) {

                $nLink = $this->linkVar($this->searchObjectByName("Targets"), "TargetsLink", $prnt);

            } 

            // Verstecke / Zeige Targets
            if ($optionsVal == 0) {

                $ergebnis = $this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Zeige Targets");

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Zeige Targets")) {

                    if ($this->doesExist($this->searchObjectByRealName("TargetsLink", $prnt))) {
                        $nLink = $this->linkVar($this->searchObjectByName("Targets"), "TargetsLink", $prnt);
                        $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Zeige Targets" => "Verstecke Targets"));
                        $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);
                    }

                } else {

                    $this->deleteObject($this->searchObjectByRealName("TargetsLink", $prnt));
                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Verstecke Targets" => "Zeige Targets"));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                }

            }

            // Modul verkleinern / Vergrößern
            if ($optionsVal == 1) {

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Modul verkleinern")) {

                    if ($scenes != null) {

                        foreach ($scenes as $scene) {

                            IPS_SetHidden($this->searchObjectByName($scene), true);

                            $this->hide($this->searchObjectByName($scene));

                            if ($this->doesExist($this->searchObjectByName($scene . " Timer"))) {

                                $this->hide($this->searchObjectByName($scene . " Timer"));

                            }
    
                        }
    
                    }

                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Modul verkleinern" => "Modul vergrößern"));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                    SetValue($this->searchObjectByName("Optionen"), -1);

                    return;

                }

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Modul vergrößern")) {

                    if ($scenes != null) {

                        foreach ($scenes as $scene) {
    
                            $this->show($this->searchObjectByName($scene));

                            if ($this->doesExist($this->searchObjectByName($scene . " Timer"))) {

                                $this->show($this->searchObjectByName($scene . " Timer"));

                            }
    
                        }
    
                    }

                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Modul vergrößern" => "Modul verkleinern"));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                }

            }

            // Start / Stop Zeitschaltung
            if ($optionsVal == 2) {

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Start")) {

                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Start" => "Stop"));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                    $this->nextElement();

                    SetValue($this->searchObjectByName("Optionen"), -1);
                    return;

                }

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "Stop")) {

                    $this->deleteObject($this->getFirstChildFrom($this->searchObjectByName("nextElement")));

                    SetValue($this->searchObjectByName("LastScene"), "");

                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("Stop" => "Start"));
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                }


            }

            // DaySets verstecken
            if ($optionsVal == 3) {

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets anzeigen")) {

                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("DaySets anzeigen" => "DaySets verstecken"));

                    $this->linkVar($this->searchObjectByName("DaySets"), "DaySets-Auswahl", $prnt, 0, true);

                    SetValue($this->searchObjectByName("Optionen"), -1);
                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);
                    return;

                }

                if ($this->profileHasAssociation($this->prefix . ".Options" . $this->InstanceID, "DaySets verstecken")) {

                    $this->changeAssociations($this->prefix . ".Options" . $this->InstanceID, array("DaySets verstecken" => "DaySets anzeigen"));

                    $this->deleteObject($this->searchObjectByName("DaySets-Auswahl", $prnt));

                    $this->addProfile($this->searchObjectByName("Optionen"), $this->prefix . ".Options" . $this->InstanceID);

                }

            }

            SetValue($this->searchObjectByName("Optionen"), -1);

        }

    }


?>