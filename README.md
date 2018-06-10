## SymconHomeMaticIntervalTimer

[![Version](https://img.shields.io/badge/Symcon_Version-5.0>-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Version](https://img.shields.io/badge/Modul_Version-1.00-blue.svg)
![Version](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/136796530/shield?branch=master)](https://github.styleci.io/repos/136796530)

Mit diesem Modul ist es möglich Schaltaktoren von [HomeMatic](http://www.eq-3.de/produkte/homematic.html) oder [HomeMaticIP](http://www.eq-3.de/produkte/homematic-ip.html) zum Beispiel für eine Gartenbeleuchtung oder sonstige Geräte automatisch in [IP-Symcon](https://www.symcon.de) zu bestimmten Zeiten ein-, bzw. auszuschalten. 
Es kann eine Astro-Funktion (Sonnenaufgang, Sonnenuntergang) oder auch ein selbstdefiniertes Zeitprofil (Einschaltzeit, Ausschaltzeit) genutzt werden.
Für die Nutzung dieses Moduls wird mindestens die Version 5.0 von IP-Symcon vorausgesetzt.
Die Entwicklung dieses Moduls findet in der Freizeit als Hobby statt.
Somit besteht auch kein Anspruch auf Fehlerfreiheit, Weiterentwicklung oder sonstige Unterstützung / Support.
Ziel ist es, den Funktionsumfang von IP-Symcon zu erweitern.

Bevor das Modul installiert wird, sollte ein Backup von IP-Symcon durchgeführt werden.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanz in IP-Symcon](#4-einrichten-der-instanz-in-ip-symcon)
5. [Variablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [GUIDs](#8-guids)
9. [Changelog](#9-changelog)
10. [Lizenz](#10-lizenz)
11. [Author](#11-author)


### 1. Funktionsumfang

- Manuelles ein- und ausschalten aller zugewiesener Schaltaktoren.
- Manuelles ein- und ausschalten der Automatikfunktion.
- Manuelles ein- und ausschalten einzelner zugewiesener Schaltaktoren.

- Automatisches ein- und ausschalten aller zugewiesener Schaltaktoren zum Sonnenaufgang und Sonnenuntergang.
- Automatisches ein- und ausschalten aller zugewiesener Schaltaktoren mittels eines definierten Zeitprofils.
- Manuelles ein- und ausschalten der zufälligen Verzögerung für die Schaltzeitpunkte.

### 2. Voraussetzungen

- IP-Symcon ab Version 5.0

### 3. Software-Installation

Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.

Bei privater Nutzung:

Nachfolgend wird die Installation dieses Moduls anhand der neuen Web-Console der Version 5.0 beschrieben.
Folgende Instanzen stehen dann in IP-Symcon zur Verfügung:

- [x] HomeMatic Interval Timer bzw. HomeMatic Zeitschaltung

Im Objektbaum von IP-Symcon die Kern-Instanzen aufrufen. Danach die [Modulverwaltung](https://www.symcon.de/service/dokumentation/modulreferenz/module-control/) aufrufen. Sie sehen nun die bereits installierten Module.
Fügen Sie über das `+` Symbol (unten rechts) ein neues Modul hinzu.
Wählen Sie als URL:

`https://github.com/ubittner/SymconHomeMaticIntervalTimer.git`  

Anschließend klicken Sie auf `OK`, um die HomeMatic Zeitschaltungsmodul zu installieren.

### 4. Einrichten der Instanz in IP-Symcon

Klicken Sie in der Objektbaumansicht unten links auf das `+` Symbol. Wählen Sie anschließen `Instanz` aus. Geben Sie im Schnellfiler das Wort "HomeMatic Zeitschaltung" ein oder wählen den Hersteller "Ulrich Bittner" aus. Wählen Sie aus der Ihnen angezeigten Liste "HomeMatic Zeitschaltung" aus und klicken Sie anschließend auf `OK`, um die Instanz zu installieren. Sie finden die Instanz unter der Hauptrubrik.

Hier zunächst die Übersicht der Konfigurationsfelder.

__Konfigurationsseite__:

Name | Beschreibung
----------------------------------- | ---------------------------------------------
(1) Allgemeine Einstellungen        | Allgemeine Einstellungen.
Kategorie                           | Kategorie für die HomeMatic Zeitschaltung.
Bezeichnung                         | Bezeichnung für die Instanz.
(2) Modus                           | Betriebsmodus.
Automatik                           | De- / Aktivieren der Automatik Funktion für Astronomie / Zeitschaltung.
(3) Einschaltzeit                   | Einschaltzeit 
Astro Auslöser                      | Unter Kerninstanzen Location wählen Sie die Objekt ID z.B. für den Sonnenuntergang aus.
Uhrzeit                             | Uhrzeit, zu der die Schaltaktoren eingeschaltet werden sollen. Astro-Auslöser muss dann 0 sein.
Zufällige Verzögerung               | De- / Aktivieren der zufälligen Verzögerung zu den definierten Zeiten.
Zeitraum in Minuten                 | Die vorgegebenen Zeiten werden um den angegebenen Zeitraum verrigert, bzw. vergrößert.
(4) Ausschaltzeit                   | Auschaltzeit
Astro Auslöser                      | Unter Kerninstanzen Location wählen Sie die Objekt ID z.B. für den Sonnenaufgang aus.
Uhrzeit                             | Uhrzeit, zu der die Schaltaktoren ausgeschaltet werden sollen. Astro-Auslöser muss dann 0 sein.
Zufällige Verzögerung               | De- / Aktivieren der zufälligen Verzögerung zu den definierten Zeiten.
Zeitraum in Minuten                 | Die vorgegebenen Zeiten werden um den angegebenen Zeitraum verrigert, bzw. vergrößert.
(5) Schaltaktoren                   | Liste der zugewiesenen Schaltaktoren.
Position                            | Positionsnummer, darf nur einmal vorhanden sein.
Bezeichnung                         | Bezeichnung des Schaltaktors / Gerätes.
Schaltaktor                         | Auswahl der HomeMatic Instanz des Schaltaktors.
Verwendung                          | De- / Aktivieren des Schaltaktors.

Geben Sie eine Bezeichung für die Zeitschaltung an, z.B. Gartenbeleuchtung, Außenbeleuchtung, Wohnzimmerlicht. 

Über das Konfigurationsfeld `Kategorie` können Sie festlegen, in welcher Kategorie die Instanz abgelegt werden sollen. Es kann auch die Hauptkategorie genutzt werden.

Wenn Sie die Daten eingetragen haben, erscheint unten im Instanzeditor eine Meldung `Die Instanz hat noch ungespeicherte Änderungen`. Klicken Sie auf den Button `Änderungen übernehmen`, um die Konfigurationsdaten zu übernehmen und zu speichern.

Sie können den Vorgang für weitere Zeitschaltung Instanzen wiederholen.

##### Hinweis:

Sofern ein Astro-Auslöser definiert wurde, so hat dieser immer Vorrang vor der definierten Uhrzeit.
Somit können beide Zeitprofile auch gemischt genutzt werden.
Bei der Nutzung der zufälligen Verzögerung muss der Abstand der definierten Schaltpunkte (3)(4) mindestens dem doppelten Wert des Verzögerungszeitraum entsprechen.
Beispiel: 
Zeitraum in Minuten 60: Einschaltzeit 11:00:00 Uhr, Ausschaltzeit mindestens 13:00 Uhr.  

### 5. Variablen und Profile

##### Variablen:

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name              | Typ       | Beschreibung
----------------- | --------- | ----------------
Devices           | Boolean   | Schaltet alle Schaltaktoren ein, bzw. aus.
Automatic         | Boolean   | Schaltet die Automatik Funktion ein, bzw. aus.
NextSwitchOnTime  | String    | Zeigt den nächsten Einschaltvorgang an.
NextSwitchOffTime | String    | Zeigt den nächsten Auschaltvorgang an.
Link1             | Link      | Link des zugeweiesenen 1. Schaltaktors
Link2             | Link      | Link des zugeweiesenen 2. Schaltaktors
Link3             | Link      | Link des zugeweiesenen n. Schaltaktors

##### Profile:

Nachfolgende Profile werden zusätzlichen hinzugefügt:

Es werden Standardprofile genutzt.

### 6. WebFront

Über das WebFront können alle zugewiesenen Schaltaktoren ein-, bzw. ausgeschaltet werden.
Ebenfalls kann die Automatikfunktion aktiviert, bzw. deaktiviert werden.
Sofern die Automatikfunktion aktiviert ist, werden den nächsten Schaltvorgänge angezeigt.
Einzelne Schaltaktoren können ebenfalls ein-, bzw. ausgeschsaltet werden.

### 7. PHP-Befehlsreferenz

Präfix des Moduls `UBHMIT` (HomeMaticIntervalTimer)

`UBHMIT_SwitchDevices(integer $InstanzID, bool $Status)`

Schaltet alle Schaltaktoren mit dem Status `true` ein und mit `false` aus.

`UBHMIT_SwitchNextDevice(integer $InstanzID)`

Bei meheren Schaltaktoren schaltet die Timer-Funktion automatisch den nächsten Schaltaktor.

`UBHMIT_SetAutomatic(integer $InstanzID, bool $Status)`

Schaltet die Automatik-Funktion mit dem Status `true` ein und mit `false` aus.

### 8. GUIDs

__Modul GUIDs__:

| Name           | GUID                                   | Bezeichnung  |
| ---------------| -------------------------------------- | -------------|
| Bibliothek     | {C913F1C0-3605-4DB9-ABB7-F44FB9B244B2} | Library GUID |
| Modul          | {17FB4E89-5D0B-4A51-B7D0-8BE6B2F46C1D} | Module GUID  |

### 9. Changelog

Version     | Datum      | Beschreibung
----------- | -----------| -------------------
1.00        | 22.05.2018 | Modulerstellung

### 10. Lizenz

CC BY-NC-SA 4.0

### 11. Author

Ulrich Bittner
