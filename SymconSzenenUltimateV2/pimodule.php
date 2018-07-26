<?php

// PISymconModule v1.1

abstract class PISymconModule extends IPSModule {

    public $moduleID = null;
    public $libraryID = null;
    public $prefix = null;
    public $instanceName = null;
    public $parentID = null;

    // Vordefinierte Variablen (müssen nicht beschrieben werden)
    public $AutomatikVar = null;
    public $SperreVar = null;

    public function __construct($InstanceID) {
        // Diese Zeile nicht löschen
        parent::__construct($InstanceID);

        $className = get_class($this);

        $moduleGUID = $this->getModuleGuidByName($className);

        $module = IPS_GetModule($moduleGUID);
        //$ownInstance = IPS_GetObject($this->InstanceID);

        //$this->instanceName = $ownInstance['ObjectName'];

        //$this->moduleID = $module['ModuleID'];
        //$this->libraryID = $module['LibraryID'];

        $moduleJsonPath = __DIR__ . "\\module.json";

        $moduleJson = json_decode(file_get_contents($moduleJsonPath));

        $this->prefix = $moduleJson->prefix;

        if ($this->doesExist($this->searchObjectByName("Automatik"))) {

            $this->AutomatikVar = $this->searchObjectByName("Automatik");

        }

        if ($this->doesExist($this->searchObjectByName("Sperre"))) {

            $this->SperreVar = $this->searchObjectByName("Sperre");

        }

        
    }

    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {

        parent::Create();

        $this->parentID = IPS_GetParent($this->InstanceID);

        $this->RegisterProperties();

        $this->CheckProfiles();

        $this->CheckVariables();

        $this->CheckScripts();


    }


    public function ApplyChanges() {

        parent::ApplyChanges(); 

        $this->CheckProfiles();

        $this->CheckVariables();

        $this->CheckScripts();

    }

    public function CheckVariables () {

        // Hier werden alle nötigen Variablen erstellt

    }

    public function RegisterProperties () {

        // Hier werden ale Properties registriert

    }

    public function CheckScripts () {

        // Hier werden alle nötigen Scripts erstellt

    }

    public function CheckProfiles () {

    }

    ##                      ##
    ##  Grundfunktionen     ##
    ##                      ##

    // PI GRUNDFUNKTIONEN


    // CheckVar Funktionen

    protected function checkVar ($var, $type = 1, $profile = false , $position = "", $index = 0, $defaultValue = null) {

        if ($position == "") {
            $position = $this->InstanceID;
        }

        if (!$this->doesExist($this->searchObjectByName($var, $position))) {
            
            $type = $this->varTypeByName($type);

            $nVar = $this->easyCreateVariable($type, $var ,$position, $index, $defaultValue);
            
            if ($type == 0 && $profile == true) {
                $this->addSwitch($nVar);
            }
            
            if ($type == 1 && $profile == true) {
                $this->addTime($nVar);
            }
            
            if ($position != "") {
                IPS_SetParent($nVar, $position);
            }
            
            if ($index != 0) {
                $this->setPosition($nVar, $index);
            }
            
            return $nVar;

        } else {

            return $this->searchObjectByName($var, $position);
        
        }
    }

    protected function checkBoolean ($name, $setProfile = false, $position = "", $index = 0, $defaultValue = null) {

        if ($name != null) {

            //echo "INDEX " . $index . " FOR " . $name . " \n";
            return $this->checkVar($name, 0, $setProfile, $position, $index, $defaultValue);

        }

    }

    protected function checkInteger ($name, $setProfile = false, $position = "", $index = 0, $defaultValue = null) {

        if ($name != null) {

            return $this->checkVar($name, 1, $setProfile, $position, $index, $defaultValue);

        }

    }

    protected function checkFloat ($name, $setProfile = false, $position = "", $index = 0, $defaultValue = null) {

        if ($name != null) {

            return $this->checkVar($name, 2, $setProfile, $position, $index, $defaultValue);

        }

    }

    protected function checkString ($name, $setProfile = false, $position = "", $index = 0, $defaultValue = null, $istAbstand = false) {

        if ($name != null && !$istAbstand) {

            return $this->checkVar($name, 3, $setProfile, $position, $index, $defaultValue);

        } else {

            if ($position == "") {

                $position = $this->InstanceID;

            }

            if (@IPS_GetObjectIDByIdent($this->InstanceID . "abstand" . $this->prefix, $position) === false) {

                $var = IPS_CreateVariable($this->varTypeByName("String"));
                $this->setPosition($var, $index);
                IPS_SetParent($var, $position);
                IPS_SetName($var, " ");
                $this->addProfile($var, "~String");

            }

        }

    }

    protected function easyCreateVariable ($type = 1, $name = "Variable", $position = "", $index = 0, $defaultValue = null) {

        if ($position == "") {

            $position = $this->InstanceID;

        }

        $newVariable = IPS_CreateVariable($type);
        IPS_SetName($newVariable, $name);
        IPS_SetParent($newVariable, $position);
        //IPS_SetPosition($newVariable, $index);
        $this->setPosition($newVariable, $index);
        IPS_SetIdent($newVariable, $this->nameToIdent($name));
        
        if ($defaultValue != null) {
            SetValue($newVariable, $defaultValue);
        }

        return $newVariable;
    }

    // Script Funktionen

    protected function easyCreateScript ($name, $script, $function = true ,$parent = "", $onlywebfront = false) {

        if ($parent == "") {

            $parent = $this->InstanceID;
        
        }

        $newScript = IPS_CreateScript(0);
        
        IPS_SetName($newScript, $name);
        IPS_SetIdent($newScript, $this->nameToIdent($name));
        
        if ($function == true) {

            if ($onlywebfront) {

                IPS_SetScriptContent($newScript, "<?php if(\$\_IPS['SENDER'] == 'WebFront') { " . $script . "(" . $this->InstanceID . ");" . "} ?>");
            
            } else {

                IPS_SetScriptContent($newScript, "<?php " . $script . "(" . $this->InstanceID . ");" . " ?>");
            
            }
        } else {

            IPS_SetScriptContent($newScript, $script);
        
        }
        
        IPS_SetParent($newScript, $parent);
        
        return $newScript;
    }

    protected function checkScript ($name, $script, $function = true, $hide = true, $position = 1000) {

        if (!$this->doesExist($this->searchObjectByName($name))) {
            
            $script = $this->easyCreateScript($name, $script, $function);
            
            if ($hide) {

                $this->hide($script);

            }

            $this->setPosition($script, $position);

            return $script;
        
        } else {
            return $this->searchObjectByName($name);
        }
    }

    protected function easyCreateOnChangeFunctionEvent ($onChangeEventName, $targetId, $function, $parent = null, $autoFunctionToText = true) {

        if ($parent == null) {

            $parent = $this->InstanceID;
        }

        if (!$this->doesExist($this->searchObjectByName($onChangeEventName, $parent))) {

            $eid = IPS_CreateEvent(0);
            IPS_SetEventTrigger($eid, 0, $targetId);
            IPS_SetParent($eid, $parent);
            if ($autoFunctionToText) {
                IPS_SetEventScript($eid, "<?php " . $this->prefix . "_" . $function . "(" . $this->InstanceID . "); ?>");
            } else {
                IPS_SetEventScript($eid, $function);
            }
            IPS_SetName($eid, $onChangeEventName);
            IPS_SetEventActive($eid, true);
            IPS_SetIdent($eid, $this->nameToIdent($onChangeEventName));

            return $eid;

        }

    }

    // Such Funktionen

    protected function searchObjectByName ($name, $searchIn = null, $objectType = null) {

        if ($searchIn == null) {

            $searchIn = $this->InstanceID;
        
        }
        

        $childs = IPS_GetChildrenIDs($searchIn);

        if (!IPS_HasChildren($searchIn)) {
            return null;
        }
        
        $returnId = 0;
        
        foreach ($childs as $child) {

            $childObject = IPS_GetObject($child);

            if ($childObject['ObjectIdent'] == $this->nameToIdent($name)) {
                
                $returnId = $childObject['ObjectID'];

            }

            if ($objectType == null) {
                
                if ($childObject['ObjectIdent'] == $this->nameToIdent($name)) {
                    
                    $returnId = $childObject['ObjectID'];

                }

            } else {
                
                if ($childObject['ObjectIdent'] == $this->nameToIdent($name) && $childObject['ObjectType'] == $this->objectTypeByName($objectType)) {
                    
                    $returnId = $childObject['ObjectID'];

                }
            }
        }

        if ($returnId == 0) {
            return "ERROR";
        }

        return $returnId;

    }

    protected function searchObjectByRealName ($name, $searchIn = null, $objectType = null) {

        if ($searchIn == null) {

            $searchIn = $this->InstanceID;
        
        }
        
        $childs = IPS_GetChildrenIDs($searchIn);
        
        $returnId = 0;
        
        foreach ($childs as $child) {

            $childObject = IPS_GetObject($child);

            if ($childObject['ObjectName'] == $name) {
                
                $returnId = $childObject['ObjectID'];

            }

            if ($objectType == null) {
                
                if ($childObject['ObjectName'] == $name) {
                    
                    $returnId = $childObject['ObjectID'];

                }

            } else {
                
                if ($childObject['ObjectName'] == $name && $childObject['ObjectType'] == $this->objectTypeByName($objectType)) {
                    
                    $returnId = $childObject['ObjectID'];

                }
            }
        }

        if ($returnId == 0) {
            return "ERROR";
        }

        return $returnId;

    }

    protected function getAllVarsByVariableCustomProfile ($name, $in = null) {

        if ($in == null) {

            $in = $this->InstanceID;

        }

        $own = IPS_GetObject($in);

        //print_r($on);

        $ary = null;

        foreach ($own['ChildrenIDs'] as $child) {

            $obj = IPS_GetObject($child);

            if ($obj['ObjectType'] == $this->objectTypeByName("variable")) {

                $obj = IPS_GetVariable($obj['ObjectID']);

                if ($obj['VariableCustomProfile'] == $name) {

                    $obj = IPS_GetObject($obj['VariableID']);
                    $ary[] = $obj['ObjectName'];

                }

            }

        }

        return $ary;

    }

    protected function getAllObjectsContainsString ($string, $searchIn = null) {

        if ($searchIn == null) {

            $searchIn = $this->InstanceID;

        }

        if (IPS_HasChildren($searchIn)) {

            $children = IPS_GetObject($searchIn);
            $children = $children['ChildrenIDs'];

            $newArray = array();

            foreach ($children as $child) {

                $child = IPS_GetObject($child);

                if (strpos($child['ObjectName'], $string) !== false) {

                    $newArray[] = $child['ObjectID'];

                }

            }

            return $newArray;

        } else {
            return null;
        }

    }

    protected function getFirstChildFrom ($id) {

        if ($this->doesExist($id)) {

            $ipsObj = IPS_GetObject($id);

            if (IPS_HasChildren($id)) {

                foreach ($ipsObj['ChildrenIDs'] as $child) {

                    return $child;
                    break;

                }

            } else {
                
                return null;

            }

        } else {

            return false;

        }

    }

    protected function nameToIdent ($name) {

        $name = str_replace(" ", "", $name);
        $name = str_replace("ä", "ae", $name);
        $name = str_replace("ü", "ue", $name);
        $name = str_replace("ö", "oe", $name);
        $name = str_replace("Ä", "Ae", $name);
        $name = str_replace("Ö", "Oe", $name);
        $name = str_replace("Ü", "Ue", $name);
        $name = preg_replace ( '/[^a-z0-9 ]/i', '', $name);

        return $name . $this->InstanceID;

    }

    // Profil Funktionen

    protected function checkVariableProfile ($name, $type, $min = 0, $max = 100, $steps = 1, $associations = null, $prefix = "", $suffix = "") {

        if (!IPS_VariableProfileExists($name)) {

            $newProfile = IPS_CreateVariableProfile($name, $type);
            IPS_SetVariableProfileValues ($name, $min, $max, $steps);
            IPS_SetVariableProfileText($name, $prefix, $suffix);
            
            if ($associations != null) {

                foreach ($associations as $assocName => $assocValue) {

                    $color = -1;

                    if (gettype("string")) {

                        if (strpos($assocValue, "|") !== false) {

                            $color = hexdec(explode("|", $assocValue)[1]);
                            $assocValue = explode("|", $assocValue)[0];

                            if ($assocValue == "true") {

                                $assocValue = true;

                            } else if ($assocValue == "false") {

                                $assocValue = false;

                            }
    
                        }

                    } else if (gettype("integer")) {

                        $assocValue = (int) $assocValue;

                    }

                    IPS_SetVariableProfileAssociation($name, $assocValue, $assocName, "", $color);

                }

            }

        }

    }

    protected function dynamicVariableProfileName ($name) {

        return $this->prefix . $this->InstanceID . "." . $name;

    }

    protected function createDynamicProfile ($profileName, $elements) {

        if ($profileName != null && count($elements) > 0 ) {

            $min = 0;
            $max = count($elements) - 1;

            if (IPS_VariableProfileExists($profileName)) {

                IPS_DeleteVariableProfile($profileName);

            }

            $this->checkVariableProfile($profileName, 1, $min, $max, 0, $elements);

        }

    }

    protected function changeAssociations ($profileName, $changeAssoc) {

        if (IPS_VariableProfileExists($profileName)) {

            $profile = IPS_GetVariableProfile($profileName);

            $name = $profileName;
            $type = $profile['ProfileType'];
            $maxVal = $profile['MaxValue'];
            $minVal = $profile['MinValue'];
            $stepSize = $profile['StepSize'];
            $digits = $profile['Digits'];
            $suffix = $profile['Suffix'];
            $prefix = $profile['Prefix'];
            $actualAssocs = $profile['Associations'];

            //$name, $type, $min = 0, $max = 100, $steps = 1, $associations = null

            $newAssocs = null;
            $blockIt = array();

            if ($changeAssoc != null) {

                // foreach ($changeAssoc as $oldName => $newName) {

                //     // echo "OLDNAME: " . $oldName . "  NewName: " . $newName . " \\n";
                //    // if ($this->profileHasAssociation($profileName, $oldName)) {

                //         foreach ($actualAssocs as $actualAssoc) {

                //             //print($actualAssoc);

                //             if ($actualAssoc['Name'] == $oldName && !in_array($actualAssoc['Name'], $blockIt)) {

                //                 //IPS_SetVariableProfileAssociation($profileName, intval($actualAssoc['Value']), $newName, $actualAssoc['Icon'], hexdec($actualAssoc['Color']));
                //                 $blockIt[] = $actualAssoc['Name']; 
                //                 $newAssocs[$newName] = intval($actualAssoc['Value']);

                //             } else {

                //                 $aname = $actualAssoc['Name'];
                //                 $newAssocs[$aname] = intval($actualAssoc['Value']);

                //             }

                //         }

                //     //}

                // }

                foreach ($actualAssocs as $actualAssoc) {

                    $toReplace = false;

                    $nname = null;
                    $nvalue = null;

                    foreach ($changeAssoc as $oldName => $newName) {

                        if ($oldName == $actualAssoc['Name']) {
                            $toReplace = true;
                            $nname = $newName;
                        }

                    }

                    if ($toReplace) {

                        $newAssocs[$nname] = $actualAssoc['Value']; 

                    } else {

                        $nme = $actualAssoc['Name'];
                        $newAssocs[$nme] = $actualAssoc['Value'];

                    }

                }

            }

            IPS_DeleteVariableProfile($profileName);
            $this->checkVariableProfile($profileName, $type, $minVal, $maxVal, $stepSize, $newAssocs);

        }

    }

    protected function removeAssociation ($profileName, $association) {

        if (IPS_VariableProfileExists($profileName)) {

            if (!$this->profileHasAssociation($profileName, $association)) {
                return;
            }

            $profile = IPS_GetVariableProfile($profileName);

            $name = $profileName;
            $type = $profile['ProfileType'];
            $maxVal = $profile['MaxValue'];
            $minVal = $profile['MinValue'];
            $stepSize = $profile['StepSize'];
            $digits = $profile['Digits'];
            $suffix = $profile['Suffix'];
            $prefix = $profile['Prefix'];
            $actualAssocs = $profile['Associations'];

            //$name, $type, $min = 0, $max = 100, $steps = 1, $associations = null

            $newAssocs = null; 
            
            if ($actualAssocs != null) {

                foreach ($actualAssocs as $assoc) {

                    if ($assoc['Name'] != $association) {

                        $assocName = $assoc['Name'];
                        $newAssocs[$assocName] = $assoc['Value'];

                    }

                }

            }

            IPS_DeleteVariableProfile($profileName);
            $this->checkVariableProfile($profileName, $type, $minVal, $maxVal, $stepSize, $newAssocs);

        }
    }

    protected function addAssociations ($profileName, $addAssocs) {

        if (IPS_VariableProfileExists($profileName)) {

            $profile = IPS_GetVariableProfile($profileName);

            $name = $profileName;
            $type = $profile['ProfileType'];
            $maxVal = $profile['MaxValue'];
            $minVal = $profile['MinValue'];
            $stepSize = $profile['StepSize'];
            $digits = $profile['Digits'];
            $suffix = $profile['Suffix'];
            $prefix = $profile['Prefix'];
            $actualAssocs = $profile['Associations'];

            $newAssocs = array();
            $blockIt = array();

            if ($addAssocs != null) {

                foreach ($actualAssocs as $actualAssoc) {

                    $nme = $actualAssoc['Name'];
                    $newAssocs[$nme] = $actualAssoc['Value'];

                }

                foreach ($addAssocs as $aa => $bb) {

                    $newAssocs[$aa] = $bb;

                }

            }

            IPS_DeleteVariableProfile($profileName);
            $this->checkVariableProfile($profileName, $type, $minVal, $maxVal, $stepSize, $newAssocs, $prefix, $suffix);

        }

    }

    protected function getValueByAssociationText ($profileName, $text) {

        if ($this->profileHasAssociation($profileName, $text)) {

            $profile = IPS_GetVariableProfile($profileName);

            foreach ($profile['Associations'] as $assoc) {

                if ($assoc['Name'] == $text) {

                    return $assoc['Value'];

                }

            }


        }

    }

    protected function getAssociationTextByValue ($profileName, $value) {

        if ($this->profileHasAssociationValue($profileName, $value)) {

            $profile = IPS_GetVariableProfile($profileName);

            foreach ($profile['Associations'] as $assoc) {

                if ($assoc['Value'] == $value) {

                    return $assoc['Name'];

                }

            }


        }

    }

    protected function profileHasAssociation ($profileName, $searchedAssoc) {

        if (IPS_VariableProfileExists($profileName)) {

            $profile = IPS_GetVariableProfile($profileName);

            if (count($profile['Associations']) > 0) {

                $found = false;

                foreach ($profile['Associations'] as $assoc) {

                    if ($assoc['Name'] == $searchedAssoc) {
                        $found = true;
                    }

                }

                return $found;

            } else {

                return false;

            }

        }

    }

    protected function profileHasAssociationValue ($profileName, $searchedValue) {

        if (IPS_VariableProfileExists($profileName)) {

            $profile = IPS_GetVariableProfile($profileName);

            if (count($profile['Associations']) > 0) {

                $found = false;

                foreach ($profile['Associations'] as $assoc) {

                    if ($assoc['Value'] == $searchedValue) {
                        $found = true;
                    }

                }

                return $found;

            } else {

                return false;

            }

        }

    }

    protected function cloneVariableProfile ($sourceProfileName, $newProfileName) {

        if (IPS_VariableProfileExists($sourceProfileName)) {

            $profile = IPS_GetVariableProfile($sourceProfileName);

            $name = $newProfileName;
            $type = $profile['ProfileType'];
            $maxVal = $profile['MaxValue'];
            $minVal = $profile['MinValue'];
            $stepSize = $profile['StepSize'];
            $digits = $profile['Digits'];
            $suffix = $profile['Suffix'];
            $prefix = $profile['Prefix'];
            $actualAssocs = $profile['Associations'];
            
            $nAssocs = array();

            if ($actualAssocs != null) {

                foreach ($actualAssocs as $actualAssoc) {

                    $aName = $actualAssoc['Name'];

                    $nAssocs[$aName] = $actualAssoc['Value'];

                }

            }

            $this->checkVariableProfile($name, $type, $minVal, $maxVal, $stepSize, $nAssocs, $prefix, $suffix);


        }

    }

    // Vereinfachende Funktionen

    protected function setPosition ($id, $position, $in = null) {

        if ($in == null) {
            $in = $this->InstanceID;
        }

        if ($this->doesExist($id)) {

            if (gettype($position) == "string") {

                if ($position == "last" || $position == "Last") {

                    $own = IPS_GetObject($in);

                    $lastChildPosition = 0;
                    $highestChildPositon = 0;

                    foreach ($own['ChildrenIDs'] as $child) {

                        $chld = IPS_GetObject($child);

                        if ($chld['ObjectPosition'] >= $highestChildPositon) {

                            $highestChildPositon = $chld['ObjectPosition'];

                        }

                    }

                    IPS_SetPosition($id, $highestChildPositon + 1);

                } else if ($position == "first" || $position == "First") {

                    $own = IPS_GetObject($in);

                    IPS_SetPosition($id, 0);

                    if (IPS_HasChildren($in)) {

                        $isfirst = true;

                        foreach ($own['ChildrenIDs'] as $child) {

                            $child = IPS_GetObject($child);

                            if ($child['ObjectPosition'] != "0" && $isfirst) {
                                break;
                            } else {
                                $isfirst = false;
                                IPS_SetPosition($child['ObjectID'], $child['ObjectPosition'] + 1);
                            }

                        }

                    }


                } else if (strpos($position, "|AFTER|") !== false) {

                    $own = IPS_GetObject($this->InstanceID);

                    $expString = explode("|AFTER|", $position);

                    $afterThisElement = $expString[1];

                    $elementFound = false;

                    // foreach ($own['ChildrenIDs'] as $child) {
                        
                    //     $childObj = IPS_GetObject($child);

                    //     if ($child == $afterThisElement) {

                    //         $subElem = false;
                    //         $lastPos = null;

                    //         foreach ($own['ChildrenIDs'] as $cld) {

                    //             $cld = IPS_GetObject($cld);

                    //             if (!$subElem) {
                    //                 $this->setPosition($cld['ObjectID'], $cld['ObjectPosition'] - 1);
                    //             } else {
                    //                 $this->setPosition($cld['ObjectID'], $lastPos + 1);
                    //             }

                    //             if ($cld['ObjectID'] == $afterThisElement) {

                    //                 $subElem = true;
                    //                 $lastPos = $cld['ObjectPosition'];

                    //             }

                    //         }

                    //         $elementFound = true;

                    //     }

                    //     if (!$elementFound) {

                    //         $this->setPosition($child, $childObj['ObjectPosition'] + 1);

                    //     } 

                    // }

                    $ownChildren = $own['ChildrenIDs'];

                    // Sortiert Children nach Position

                    usort($ownChildren, function($a, $b) {

                        $go1 = IPS_GetObject($a);
                        $go2 = IPS_GetObject($b);
                        
                        return $go1['ObjectPosition'] > $go2['ObjectPosition'];
                    
                    });

                    foreach ($ownChildren as $child) {

                        $obj = IPS_GetObject($child);

                        if ($child == $afterThisElement) {

                            $elementFound = true;
                            $subElementFound = false;

                            foreach ($ownChildren as $subChild) {

                                $oo = IPS_GetObject($subChild);
                                if ($subChild == $child) {
                                    $subElementFound = true;
                                } else {
                                    if (!$subElementFound) {
                                        $this->setPosition($child, $oo['ObjectPosition'] - 1);
                                    }
                                }

                            }
                            $this->setPosition($child, $obj['ObjectPosition']);
                            $this->setPosition($id, $obj['ObjectPosition'] + 1);

                        } else {

                            if ($elementFound) {

                                $this->setPosition($child, $obj['ObjectPosition'] + 1);

                            } else {

                                $this->setPosition($child, $obj['ObjectPosition']);

                            }

                        }

                    }

                }

            } else {

                IPS_SetPosition($id, $position);

            }

        }

    }

    protected function addSwitch ($vid) {

        if(IPS_VariableProfileExists("Switch"))
        {
            IPS_SetVariableCustomProfile($vid,"Switch");
            $this->addSetValue($vid);
        } else {

            $this->checkVariableProfile("Switch", $this->varTypeByName("boolean"), 0.00, 1.00, 1.00, array("Aus" => false, "An" => "true|0x8000FF"));

        }

    }

    protected function addSetValue ($id) { 

        if (!$this->doesExist($this->searchObjectByName("SetValue"))) {

            $setValueScript = $this->checkScript("SetValue", "<?php SetValue(\$IPS_VARIABLE, \$IPS_VALUE); ?>", false);
            $this->hide($setValueScript);

            IPS_SetVariableCustomAction($id, $this->searchObjectByName("SetValue"));

        } else {

            IPS_SetVariableCustomAction($id, $this->searchObjectByName("SetValue"));

        }

    }

    protected function addTime ($vid) {

        if (IPS_VariableProfileExists("~UnixTimestampTime")) {

            IPS_SetVariableCustomProfile($vid, "~UnixTimestampTime");
            IPS_SetVariableCustomAction($vid, $this->searchObjectByName("~UnixTimestampTime"));
        
        }
    }

    protected function doesExist ($id) {

        if (gettype($id) != "integer") {
            return false;
        }

        if (IPS_ObjectExists($id) && $id != 0) {
            
            return true;

        } else {

            return false;
        
        }
    }

    protected function addProfile ($id, $profile, $useSetValue = true) {

        if (IPS_VariableProfileExists($profile)) {

            IPS_SetVariableCustomProfile($id, $profile);
            
            if ($useSetValue) {

                $this->addSetValue($id);
            
            }
        } else {

            //echo $profile . " does not exist!";

        }
    }

    protected function hide ($id) {

        IPS_SetHidden($id, true);

    }

    protected function checkFolder ($name, $parent = null ,$index = 100000) {
        
        if ($parent == null || $parent == 0) {
            $parent = $this->InstanceID;
        }

        if ($this->doesExist($this->searchObjectByName($name, $parent)) == false) {
            
            $targets = $this->createFolder($name);
            
            $this->hide($targets);
            
            if ($index != null ) {
                
                IPS_SetPosition($targets, $index);
            
            }
            
            if ($parent != null) {
                
                IPS_SetParent($targets, $parent);
            
            }
            
            return $targets;

        } else {

            return $this->searchObjectByName($name, $parent);

        }
    }

    protected function createFolder ($name) {

        $units = IPS_CreateInstance($this->getModuleGuidByName());
        IPS_SetName($units, $name);
        IPS_SetIdent($units, $this->nameToIdent($name));
        IPS_SetParent($units, $this->InstanceID);
        return $units;

    }

    protected function getModuleGuidByName ($name = "Dummy Module") {
        
        $allModules = IPS_GetModuleList();
        $GUID = ""; 
        
        foreach ($allModules as $module) {

            if (IPS_GetModule($module)['ModuleName'] == $name) {
                $GUID = $module;
                break;
            }

        }

        return $GUID;
    } 

    protected function setIcon ($objectId, $iconName) {

        $object = IPS_GetObject($objectId);

        if ($object['ObjectIcon'] != $iconName) {

            $iconList = $this->getIconList();

            if (in_array($iconName, $iconList)) {

                IPS_SetIcon($objectId, $iconName);

            } else {

                echo "Icon existiert nicht!";

            }

        }

    }

    protected function getIconList () {

        $ary =  array("Aircraft", "Alert", "ArrowRight", "Backspace", "Basement", "Bath", "Battery", "Bed", "Bike", "Book", "Bulb", "Calendar", "Camera", "Car", "Caret", "Cat", "Climate", "Clock", "Close", "CloseAll", "Cloud", "Cloudy", "Cocktail", "Cross", "Database", "Dining", "Distance", "DoctorBag", "Dog", "Dollar", "Door", "Download", "Drops", "Duck", "Edit", "Electricity", "EnergyProduction", "EnergySolar", "EnergyStorage", "ErlenmeyerFlask", "Euro", "Execute", "Eyes", "Factory", "Favorite", "Female", "Fitness", "Flag", "Flame", "FloorLamp", "Flower", "Fog", "Garage", "Gas", "Gauge", "Gear", "Graph", "GroundFloor", "Handicap", "Heart", "Help", "HollowArrowDown", "HollowArrowLeft", "HollowArrowRight", "HollowArrowUp", "HollowDoubleArrowDown", "HollowDoubleArrowLeft", "HollowDoubleArrowRight", "HollowDoubleArrowUp", "HollowLargeArrowDown", "HollowLargeArrowLeft", "HollowLargeArrowRight", "HollowLargeArrowUp", "Hourglass", "HouseRemote", "Image", "Information", "Intensity", "Internet", "IPS", "Jalousie", "Key", "Keyboard", "Kitchen", "Leaf", "Light", "Lightning", "Link", "Lock", "LockClosed", "LockOpen", "Macro", "Mail", "Male", "Melody", "Menu", "Minus", "Mobile", "Moon", "Motion", "Move", "Music", "Network", "Notebook", "Ok", "Pacifier", "Paintbrush", "Pants", "Party", "People", "Plug", "Plus", "Popcorn", "Power", "Presence", "Radiator", "Raffstore", "Rainfall", "Recycling", "Remote", "Repeat", "Return", "Robot", "Rocket", "Script", "Shift", "Shower", "Shuffle", "Shutter", "Sink", "Sleep", "Snow", "Snowflake", "Sofa", "Speaker", "Speedo", "Stars", "Sun", "Sunny", "Talk", "Tap", "Teddy", "Tee", "Telephone", "Temperature", "Thunder", "Title", "TopFloor", "Tree", "TurnLeft", "TurnRight", "TV", "Umbrella", "Unicorn", "Ventilation", "Warning", "Wave", "Wellness", "WindDirection", "WindSpeed", "Window", "WC", "XBMC");
        return $ary;

    }

    protected function show ($id) {
        IPS_SetHidden($id, false);
    }

    protected function varTypeByName ($name) {

        $name = (string) $name;

        $booleanAlias = array("Boolean", "boolean", "bool", "Bool", "b", "B");
        $integerAlias = array("Integer", "integer", "Int", "int", "i", "I", 1);
        $floatAlias = array("Float", "float", "fl", "Fl", 2);
        $stringAlias = array("String", "string", "str", "Str", "s", "S", 3);

        if (in_array($name, $booleanAlias)) {

            return 0; 

        } else if (in_array($name, $integerAlias)) {

            return 1;

        } else if (in_array($name, $floatAlias)) {

            return 2;

        } else if (in_array($name, $stringAlias)) {

            return 3;

        }

    }

    protected function objectTypeByName ($name) {

        //0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link)
        $kategorieAlias = array("Kategorie", "kategorie", "Category", "category", "Kat", "kat", "Cat", "cat");
        $instanzAlias = array("Instanz", "instanz", "Instance", "instance", "Module", "module", "Modul", "modul");
        $variableAlias = array("Variable", "variable", "var", "Var");
        $scriptAlias = array("Script", "script", "Skript", "Skript");
        $ereignisAlias = array("Ereignis", "ereignis", "Event", "event", "Trigger", "trigger");
        $mediaAlias = array("Media", "media", "File", "file");
        $linkAlias = array("Link", "link", "Verknüpfung", "verknüpfung");

        if (in_array($name, $kategorieAlias)) {

            return 0;

        } else if (in_array($name, $instanzAlias)) {

            return 1;

        } else if (in_array($name, $variableAlias)) {

            return 2;

        } else if (in_array($name, $scriptAlias)) {

            return 3;

        } else if (in_array($name, $ereignisAlias)) {

            return 4;

        } else if (in_array($name, $mediaAlias)) {

            return 5;

        } else if (in_array($name, $linkAlias)) {

            return 6;

        }

    } 

    // $varNames Beispiel: array("Element 1|false|1>onElement1Change", "Element 2|true|2")
    //                     array("Name|DefaultVal|Index")
    protected function createSwitches ($varNames, $position = null) {

        if ($position == null) {

            $position = $this->InstanceID;

        } else {
            $position = $position->InstanceID;
        }

        $index;

        $IDs = null;

        foreach ($varNames as $varName) {

            $vrnme = "";
            $completeStr = $varName;

            if (strpos($varName, '|') !== false) {

                $completeName = $varName;

                $expl = explode("|", $varName);
                $defaultValue = $expl[1];
                $varName = $expl[0];
                $vrnme = $varName;

                if ($defaultValue == "true") {
                    $defaultValue = true;
                } else {
                    $defaultValue = false;
                }

                //print_r($expl);

                if (count($expl) > 1){

                    $index = intval($expl[2]);

                } else {
                    $index = 0;
                }

            } else {
                $defaultValue = null;
                $index = 0;
            }

            $idd = $this->checkBoolean($varName, true, $position, $index, $defaultValue);

            if (strpos($completeStr, '>') !== false) {

                $functionName = explode(">", $completeStr)[1];

                //echo $idd . "|" . $functionName;

                $this->createOnChangeEvents(array($idd . "|" . $functionName), $position);                

            }

            $IDs[] = $idd;

            $this->setIcon($idd, "Power");

        }

        return $IDs;

    }

    protected function linkVar ($target, $linkName = "Unnamed Link", $parent = null, $linkPosition = 0, $ident = false) {

        if ($parent == null) {
            $parent = $this->InstanceID;
        }

        if ($this->doesExist($target)) {

            if (!$this->doesExist($this->searchObjectByRealName($linkName, $parent))) {

                $link = IPS_CreateLink();
                IPS_SetName($link, $linkName);

                if ($ident == true) {

                    IPS_SetIdent($link, $this->nameToIdent($linkName));

                }

                IPS_SetParent($link, $parent);
                IPS_SetLinkTargetID($link, $target);
                IPS_SetHidden($link, false);
                $this->setPosition($link, $linkPosition);

                return $link;
            }

        }

    }

    protected function getHighestPosition ($in = null) {

        if ($in == null) {

            $in = $this->InstanceID;

        }

        $obj = IPS_GetObject($in);

        $maxPos = 0;

        if (count($obj['ChildrenIDs']) > 0) {

            foreach ($obj['ChildrenIDs'] as $child) {

                $child = IPS_GetObject($child);

                if ($child['ObjectPosition'] >= $maxPos) {

                    $maxPos = $child['ObjectPosition'];

                }

            }

        }

        return $maxPos;

    }

    protected function orderChildrenByPosition ($objID) {

        if (IPS_HasChildren($objID)) {

            $children = IPS_GetObject($objID);
            $children = $children['ChildrenIDs'];
            
            usort($children, function($a, $b) {

                $go1 = IPS_GetObject($a);
                $go2 = IPS_GetObject($b);
                
                return $go1['ObjectPosition'] > $go2['ObjectPosition'];
            
            });

            return $children;

        }

    }

    // Sort

    public static function cmp ($a, $b) {

        return $a['ObjectPosition'] > $b['ObjectPosition'];

    }

    //

    protected function linkCompleteDummy ($source, $target) {

        $sourceObj = IPS_GetObject($source);
        $targetObj = IPS_GetObject($target);

        foreach ($sourceObj as $sourceChild) {

            $sChildObj = IPS_GetObject($sourceChild);

            $alreadyLinked = false;

            if (count($targetObj['ChildrenIDs']) > 0) {

              foreach ($targetObj['ChildrenIDs'] as $targetChild) {

                $targetChildObj = IPS_GetObject($targetChild);

                if ($targetChildObj['ObjectType'] == $this->objectTypeByName("Link")) {

                    $tg = IPS_GetLink($targetChildObj);

                    if ($tg['TargetID'] == $sourceChild) {

                        $alreadyLinked = true;

                    }

                }

              }  

            }
            
            if (!$alreadyLinked) {

                $this->linkVar($sChildObj['ObjectID'], $sChildObj['ObjectName'], $targetObj['ObjectID']);

            }

        }

    }

    protected function deleteObject ($id) {

        if ($id == 0) {
            return null;
        }

        if (!$this->doesExist($id)) {
            return null; 
        }

        $obj = IPS_GetObject($id);

        if ($obj['ObjectType'] == $this->objectTypeByName("Variable")) {
            IPS_DeleteVariable($id);
        } else if ($obj['ObjectType'] == $this->objectTypeByName("Event")) {
            IPS_DeleteEvent($id);
        } else if ($obj['ObjectType'] == $this->objectTypeByName("Link")) {
            IPS_DeleteLink($id);
        } else if ($obj['ObjectType'] == $this->objectTypeByName("Script")) {
            if (IPS_HasChildren($id)) {
                foreach ($obj['ChildrenIDs'] as $child) {
                    $this->deleteObject($child);
                }
            }
            IPS_DeleteScript($id, true);
        } else if ($obj['ObjectType'] == $this->objectTypeByName("Kategorie")) {
            if (IPS_HasChildren($id)) {
                foreach ($obj['ChildrenIDs'] as $child) {
                    $this->deleteObject($child);
                }
            }
            IPS_DeleteCategory($id);
        } else if ($obj['ObjectType'] == $this->objectTypeByName("Instance")) {
            if (IPS_HasChildren($id)) {
                foreach ($obj['ChildrenIDs'] as $child) {
                    $this->deleteObject($child);
                }
            }
            IPS_DeleteInstance($id);
        }

    }

    protected function setDevice ($deviceID, $wert){


        if ($this->SperreVar != null){

            $sperre = GetValue($this->SperreVar);

            if ($sperre) {

                return;

            }

        }

        $device = IPS_GetObject($deviceID);
    
        switch($device['ObjectType']){
        
            case 1:
                    $instance = IPS_GetInstance($device['ObjectID']);
                    
                    //wenn EIB Groub
                    if ($instance['ModuleInfo']['ModuleName'] == "EIB Group"){
                        
                        if(IPS_HasChildren($device['ObjectID']) == 1) {
                            
                            foreach(IPS_GetChildrenIDs($device['ObjectID']) as $child){	
                            
                                $childVar = IPS_GetVariable($child);
                                
                                //wenn bool / Switch
                                if($childVar['VariableType'] == 0){
                                    
                                    EIB_Switch($device['ObjectID'], $wert);
                                
                                }
                                
                                //wenn int / Dim / float
                                if($childVar['VariableType'] == 1 || $childVar['VariableType'] == 2) {
                                    if(is_int($wert) || is_float($wert)){
                                        
                                        EIB_DimValue($device['ObjectID'], $wert);								
                                    
                                    }else{
                                        
                                        if(is_bool($wert)){
                                        
                                            if($wert === true){
                                                $wert = 100;
                                                EIB_DimValue($device['ObjectID'], $wert);
                                                
                                            }
                                            else{
                                                $wert = 0;
                                                EIB_DimValue($device['ObjectID'], $wert);
                                                
                                            }
                                        }
                                    }	
                                    
                                }
                        
                            }
                    
                        }
                    
                    }else{
                      
                        //Homematic Support (Aktuell: Switch)
                        if ($instance['ModuleInfo']['ModuleName'] == "HomeMatic Device") {
                
                             HM_WriteValueBoolean($device['ObjectID'], "STATE", $wert);
        
                        }
                    }
                break;
            
            case 2:
                    $getVar = IPS_GetVariable($device['ObjectID']);
                
                    $parent = IPS_GetParent($device['ObjectID']);
                    
                    if(IPS_GetObject($parent)['ObjectType'] == 1){
                    
                        $parentInstanz = IPS_GetInstance($parent);
                    
                        if($parentInstanz['ModuleInfo']['ModuleName'] == "EIB Group" ||  $parentInstanz['ModuleInfo']['ModuleName'] == "HomeMatic Device" ){
                    
                            $this->setDevice($parent, $wert);
                        
                        } else if ($parentInstanz['ModuleInfo']['ModuleName'] == "Dummy Module") {
                        
                            if (gettype($wert) == "boolean") {
                            
                                if ($wert) {
                                    $wert = 100;
                                } else {
                                    $wert = 0;
                                }
                                
                                SetValue($deviceID, $wert);
                                
                            } else {
                            
                                SetValue($deviceID, $wert);
                                
                            }
                            
                        }
                    } else {
                    
                    // wenn bool
                    if ($getVar['VariableType'] == 0) {
                
                        SetValue($device['ObjectID'], $wert);
                    }
                        
                    // wenn int oder floar
                    if($getVar['VariableType'] == 1 || $getVar['VariableType'] == 2) { 
                        
                        if(is_int($wert) || is_float($wert)){
                            
                            SetValue($device['ObjectID'], $wert);
                        }
                        else {
                            if(is_bool($wert)){              
                                if($wert == true){
                                
                                    $wert = 100;
                            
                                    SetValue($device['ObjectID'], $wert);
                                }
                                if($wert == false){
                                    
                                    $wert = 0;
                                    SetValue($device['ObjectID'], $wert);
                                }
                            }
                        
                        }
                    }	
                    
                    }
                    
                break;
                
        
        }
        
    }
    
    protected function getElementAfterInArray ($search, $array) {

        $elementFound = false;
        $elem = "";
        $counter = 0;
        $isLast = 0;


        foreach ($array as $element) {

            if ($elementFound) {
                $elem = $element;
                break;
            }

            if ($element == $search) {
                $elementFound = true;
            }

            if ($counter == count($array) - 1) {
                return "last";
            }
            
            $counter++;

        }

        if ($elem == "") {
            return null;
        }else{
            return $elem;
        }

    }

    protected function setAllInLinkList ($linkListId, $value) {

        $linkListObj = IPS_GetObject($linkListId);

        if (count($linkListObj['ChildrenIDs']) > 0) {

            foreach ($linkListObj['ChildrenIDs'] as $child) {

                $child = IPS_GetObject($child);

                if ($child['ObjectType'] == $this->objectTypeByName("Link")) {

                    $child = IPS_GetLink($child['ObjectID']);
                    $tg = $child['TargetID'];

                    $this->setDevice($tg, $value);

                }

            }

        }

    }

    // "is" Funktionen

    protected function isBaseFunction ($id, $is) {
        if ($id != 0 && $id != null) {

            $obj = IPS_GetObject($id);

            if ($obj['ObjectType'] == $is) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    protected function isCategory ($id) {

        return $this->isBaseFunction($id, 0);

    }

    protected function isInstance ($id) {

        return $this->isBaseFunction($id, 1);

    }

    protected function isVariable ($id) {

        return $this->isBaseFunction($id, 2);

    }

    protected function isScript ($id) {

        return $this->isBaseFunction($id, 3);

    }

    protected function isEvent ($id) {

        return $this->isBaseFunction($id, 4);

    }

    protected function isMedia ($id) {

        return $this->isBaseFunction($id, 5);

    }

    protected function isLink ($id) {

        return $this->isBaseFunction($id, 6);

    }

    
    // Kern Instanzen bekommen

    protected function getCoreInstanceBase ($instanceName) {

        $all = IPS_GetObject(0);

        if (IPS_HasChildren($all['ObjectID'])) {

            $found = false;

            foreach ($all['ChildrenIDs'] as $child) {

                if ($this->isInstance($child) && $child != 0) {

                    $child = IPS_GetInstance($child);

                    if ($child['ModuleInfo']['ModuleName'] == $instanceName) {

                        $found = true;
                        return $child['InstanceID'];

                    }

                }

            }

            if (!$found) {

                return null;

            }

        }

    }

    protected function getArchiveControlInstance () {

        return $this->getCoreInstanceBase("Archive Control");

    }

    protected function getWebfrontInstance () {

        return $this->getCoreInstanceBase("WebFront Configurator");

    }

    protected function getNotificationControlInstance () {

        return $this->getCoreInstanceBase("Location Control");

    }

    protected function getWebhookControlInstance () {

        return $this->getCoreInstanceBase("WebHook Control");

    }

    protected function activateVariableLogging ($id) {

        if ($id == 0 || $id == null) {
            return;
        }

        $archiveInstance = $this->getArchiveControlInstance();

        if ($archiveInstance != null && $archiveInstance != 0) {

            AC_SetLoggingStatus ($archiveInstance, $id, true);
            IPS_ApplyChanges($archiveInstance);

        }

    }

    // String Analyizer
    
    protected function idIsNotNullOrEmpty ($id) {

        if ($id != null && $id != 0) {
            return true;
        } else {
            return false;
        }

    }


    // Starke vereinfachungen


    // Erstellt Automatik und SperrVariable sowie Automatik onChange Event (FunktionName: onAutomatikChange()) 
    protected function createBasePIModuleElements ($parent = null) {

        if ($parent == null) {
            $parent = $this->InstanceID;
        }

        $elements = array();

        $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1"));

        $elements[0] = $switches[0];
        $elements[1] = $switches[1];

        //($onChangeEventName, $targetId, $function, $parent = null, $autoFunctionToText = true)
        $elements[2] = $this->easyCreateOnChangeFunctionEvent("onChange Automatik", $elements[0], "onAutomatikChange", $parent);

        return $elements;

    }

    // array("TargetID|Function")
    protected function createOnChangeEvents ($ary, $parent = null) {

        if ($parent == null) {
            $parent = $this->InstanceID;
        }

        $newEvents = array();

        if ($ary != null) {

            if (count($ary) > 0) {

                foreach ($ary as $funcString) {

                    if (strpos($funcString, "|") !== false) {

                        $funcAry = explode("|", $funcString);
                        $targetID = intval($funcAry[0]);
                        $function = $funcAry[1];

                        $newName = IPS_GetName($targetID);
                        $newName = "onChange " . $newName;

                        $newEvents[] = $this->easyCreateOnChangeFunctionEvent($newName, $targetID, $function, $parent);

                    }

                }

            }

        }

    }



}

?>