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

        }


        public function CheckVariables () {

            $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1"));

            $szenen = $this->checkInteger("Optionen", false, null, 2, 0);
            
            $targets = $this->checkFolder("Targets", null, 3);

            $this->checkVariableProfile("DASISTEINTEST", $this->varTypeByName("boolean"), 0, 1, 1, array("Aus" => "0", "An" => "1"));
    
        }
    
        public function RegisterProperties () {
    
            // Hier werden ale Properties registriert
    
        }
    
        public function CheckScripts () {
    
            // Hier werden alle nötigen Scripts erstellt
    
        }
    

 
    }
?>