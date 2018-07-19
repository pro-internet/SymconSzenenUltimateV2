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

            $this->checkSceneVars();

            $this->checkSceneTimerVars();

            $this->easyCreateOnChangeFunctionEvent("onChange Optionen", $this->searchObjectByName("Optionen"), "onOptionsChange", $this->searchObjectByName("Events"));

            $this->updateSceneVarProfile();

        }


        public function CheckVariables () {

            $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1"));

            $optionen = $this->checkInteger("Optionen", false, null, 2, -1);

            $sceneVar = $this->checkInteger("Szenen", false, null, 3, 0);

            $targets = $this->checkFolder("Targets", null, 4);
            $events = $this->checkFolder("Events", null, 5);

            //$name, $setProfile = false, $position = "", $index = 0, $defaultValue = null, $istAbstand = false
            $this->checkString("", false, $this->InstanceID, "|AFTER|" . $sceneVar, null, true);

            $this->addProfile($optionen, $this->prefix . ".Options");
            $this->addProfile($sceneVar, $this->prefix . ".ScenesVarProfile." . $this->InstanceID);

            $this->setIcon($optionen, "Database");
            $this->setIcon($switches[0], "Power");
            $this->setIcon($switches[1], "Power");
            $this->setIcon($sceneVar, "Rocket");

            $this->addSetValue($optionen);
    
        }
    
        public function RegisterProperties () {
    
            $this->RegisterPropertyBoolean("ModeDaySet", false);
            $this->RegisterPropertyString("Names", "");
            $this->RegisterPropertyBoolean("ModeTime", false);
            $this->RegisterPropertyBoolean("Loop", false);
    
        }
    
        public function CheckScripts () {
    
            // Hier werden alle nötigen Scripts erstellt
    
        }

        public function CheckProfiles () {

            //checkVariableProfile ($name, $type, $min = 0, $max = 100, $steps = 1, $associations = null) {
            $this->checkVariableProfile($this->prefix . ".Options", $this->varTypeByName("int"), 0, 3, 0, array("Zeige Targets" => 0, "Verstecke Targets" => 1, "Modul verkleinern" => 2, "Modul vergrößern" => 3));
            $this->checkVariableProfile($this->prefix . ".SceneOptions", $this->varTypeByName("int"), 0, 1, 0, array("Speichern" => 0, "Ausführen" => 1));
            $this->checkVariableProfile($this->prefix . "SceneTimerVar", $this->varTypeByName("int"), 0, 3600, 1, null);
        }

        #                            #
        #   Modulspez. Funktionen    #
        #                            #

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
                        $this->addSetValue($newInt);
                        $this->setIcon($newInt, "Rocket");
                        $this->addProfile($newInt, $this->prefix . ".SceneOptions");

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

                foreach ($allSceneVars as $sceneVar) {

                    $doesExist = false;

                    $sceneVarObj = IPS_GetObject($this->searchObjectByName($sceneVar));

                    if (count($allTimerVars) > 0) {

                        foreach ($allTimerVars as $timerVar) {

                            $timerVarObj = IPS_GetObject($timerVar);

                            if ($timerVarObj['ObjectName'] == $sceneVarObj['ObjectName'] . " Timer") {

                                $doesExist = true;

                            }

                        }

                    }

                    if (!$doesExist) {

                        $checkTimer = $this->checkInteger($sceneVarObj['ObjectName'] . " Timer", false, "", "|AFTER|" . $this->searchObjectByname($sceneVar), 10);
                        $this->setIcon($checkTimer, "Clock");
                        $this->addProfile($checkTimer, $this->prefix . "SceneTimerVar");
                        $this->addSetValue($checkTimer);

                    }

                }
                

            }

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
        
        public function onOptionsChange () {

            $optionsVal = GetValue($this->searchObjectByName("Optionen"));
            $prnt = IPS_GetParent($this->InstanceID);
            $own = IPS_GetObject($this->InstanceID);
            $scenes = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneOptions");
            $timers = $this->getAllVarsByVariableCustomProfile($this->prefix . ".SceneTimerVar");

            // Zeige Targets
            if ($optionsVal == 0) {

                $nLink = $this->linkVar($this->searchObjectByName("Targets"), "TargetsLink", $prnt);

            } 

            // Verstecke Targets
            if ($optionsVal == 1) {

                if ($this->doesExist($this->searchObjectByRealName("TargetsLink", $prnt))) {
                    $this->deleteObject($this->searchObjectByRealName("TargetsLink", $prnt));
                }

            }

            // Modul verkleinern
            if ($optionsVal == 2) {

                if (count($scenes) > 0) {

                    foreach ($scenes as $scene) {

                        $this->hide($this->searchObjectByName($scene));

                    }

                }

                if (count($timers) > 0) {

                    foreach ($timers as $timer) {

                        $this->hide($this->searchObjectByName($timer));

                    }

                }

            }

            // Modul vergrößern
            if ($optionsVal == 3) {

                if (count($scenes) > 0) {

                    foreach ($scenes as $scene) {

                        $this->show($this->searchObjectByName($scene));

                    }

                }

                if (count($timers) > 0) {

                    foreach ($timers as $timer) {

                        $this->show($this->searchObjectByName($timer));

                    }

                }

            }

            SetValue($this->searchObjectByName("Optionen"), -1);

        }

 
    }
?>