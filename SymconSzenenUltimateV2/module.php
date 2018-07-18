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

            $this->easyCreateOnChangeFunctionEvent("onChange Optionen", $this->searchObjectByName("Optionen"), "onOptionsChange", $this->searchObjectByName("Events"));

        }


        public function CheckVariables () {

            $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1"));

            $optionen = $this->checkInteger("Optionen", false, null, 2, -1);

            $targets = $this->checkFolder("Targets", null, 3);
            $events = $this->checkFolder("Events", null, 4);

            $title = $this->checkString("Szenen", false, null, 5, "");

            $this->addProfile($optionen, $this->prefix . ".Options");
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
            $this->checkVariableProfile($this->prefix . ".Options", $this->varTypeByName("int"), 0, 1, 1, array("Zeige Targets" => 0, "Verstecke Targets" => 1));

        }

        ##                 ##
        ## OnChange Events ##
        ##                 ##
        
        public function onOptionsChange () {

            echo "OptionsChanged :)";

        }

 
    }
?>