# SymconSzenenV2
SymconSzenenUltimate gibt die Möglichkeit zu 3 verschiedenen Modi:
### Als SzenenDaySet
* Hierbei können beliebig viele Szenen erstellt werden und ein DaySet Sensor kann angegeben werden. Nun kann für jedes DaySet eine Szene ausgewählt werden, welche automatisch ausgeführt wird (Außer die Automatik ist deaktiviert oder die Sperre ist aktiviert)
### Als SzenenZeitSteuerung
* Man kann beliebig viele Szenen erstellen und für jede Szene einen Timer stellen. Drückt man nun auf Start werden die Szenen nach der Reihe so lange wie ihr Timer ausgeführt.
* Ist "Zeitschaltung loopen" deaktiviert werden am Ende der Schlange alle Werte der Targets auf false gesetzt
### Als Szenen Modul
* Aktiviert man keinen der Haken, so kann das Modul als normales SzenenModul verwendet werden
## Öffentliche Funktionen
### SymconSzenenV2_SetScene($instanzID, $szenenID);
* Schaltet Targets auf angegebenen Wert
### SymconSzenenV2_Start($instanzID);
* Startet die Zeitschaltung 
### SymconSzenenV2_Stop($instanzID);
* Stoppt die Zeitschaltung 
## :warning: Hinweis
* Die Szenen können noch über das WebFront an der "Szene" Variable verändert werden, jedoch können die Szenen von Außen nur über ein SymconSzenenV2_SetScene($instanzID, $szenenID) geschaltet werden. 

# IP-Symcon Versionen
| Branch        | IPS 5.0           | IPS 4.2  |
| ------------- |:-------------:| -----:|
| master     | :x: | :white_check_mark: |
| master_dev      | :white_check_mark: | :x: |

* :white_check_mark: Windows
* :white_check_mark: Linux
