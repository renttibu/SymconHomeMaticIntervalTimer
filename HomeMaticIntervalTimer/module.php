<?php

/*
 * @module      HomeMatic Interval Timer
 *
 * @file		module.php
 *
 * @author		Ulrich Bittner
 * @copyright	(c) 2018
 * @license     CC BY-NC-SA 4.0
 *
 * @version		1.00
 * @date		2018-05-22, 19:15
 * @lastchange  2018-05-22, 19:15
 *
 * @see			https://github.com/ubittner/SymconHomeMaticIntervalTimer.git
 *
 * @guids		Library
 * 				{C913F1C0-3605-4DB9-ABB7-F44FB9B244B2}
 *
 *              Module
 *              {17FB4E89-5D0B-4A51-B7D0-8BE6B2F46C1D}
 *
 * @changelog	2018-05-22, 19:15, initial module script version 1.00
 *
 */

declare(strict_types=1);

// Definitions
if (!defined('HOMEMATIC_INTERVAL_TIMER')) {
    define('HOMEMATIC_INTERVAL_TIMER', '{17FB4E89-5D0B-4A51-B7D0-8BE6B2F46C1D}');
}

if (!defined('LOCATION_CONTROL')) {
    define('LOCATION_CONTROL', '{45E97A63-F870-408A-B259-2933F7EABF74}');
}

if (!defined('WEBFRONT_GUID')) {
    define('WEBFRONT_GUID', '{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
}

class HomeMaticIntervalTimer extends IPSModule
{
    public function Create()
    {
        // Never delete this line!
        parent::Create();
        // Register properties
        // General settings
        $this->RegisterPropertyInteger('Category', 0);
        $this->RegisterPropertyString('Description', $this->Translate('Interval timer'));
        // Mode
        $this->RegisterPropertyBoolean('UseAutomatic', false);
        // Switch on time
        $this->RegisterPropertyBoolean('UseSwitchOnTime', false);
        $this->RegisterPropertyInteger('SwitchOnAstroID', 0);
        $this->RegisterPropertyString('SwitchOnTime', '{"hour":22,"minute":30,"second":0}');
        $this->RegisterPropertyBoolean('UseRandomSwitchOnDelay', false);
        $this->RegisterPropertyInteger('SwitchOnDelay', 30);
        // Switch off time
        $this->RegisterPropertyBoolean('UseSwitchOffTime', false);
        $this->RegisterPropertyInteger('SwitchOffAstroID', 0);
        $this->RegisterPropertyString('SwitchOffTime', '{"hour":8,"minute":30,"second":0}');
        $this->RegisterPropertyBoolean('UseRandomSwitchOffDelay', false);
        $this->RegisterPropertyInteger('SwitchOffDelay', 30);
        // Device list
        $this->RegisterPropertyString('DeviceList', '');
        $this->RegisterPropertyInteger('DeviceSwitchingDelay', 0);
        // Register timer
        $this->RegisterTimer('SwitchDevicesOn', 0, 'UBHMIT_SwitchDevices($_IPS[\'TARGET\'], true);');
        $this->RegisterTimer('SwitchDevicesOff', 0, 'UBHMIT_SwitchDevices($_IPS[\'TARGET\'], false);');
        $this->RegisterTimer('SwitchNextDevice', 0, 'UBHMIT_SwitchNextDevice($_IPS[\'TARGET\']);');
        // Register variables
        $this->RegisterVariableBoolean('Devices', $this->Translate('Devices'), '~Switch', 1);
        $this->EnableAction('Devices');
        $this->RegisterVariableBoolean('Automatic', $this->Translate('Automatic'), '~Switch', 2);
        IPS_SetIcon($this->GetIDForIdent('Automatic'), 'Clock');
        $this->EnableAction('Automatic');
        $this->RegisterVariableString('NextSwitchOnTime', $this->Translate('Next switch on'), '', 3);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchOnTime'), 'Information');
        $this->RegisterVariableString('NextSwitchOffTime', $this->Translate('Next switch off'), '', 4);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchOffTime'), 'Information');
    }

    public function ApplyChanges()
    {
        // Never delete this line!
        parent::ApplyChanges();
        // Register messages
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
        if (IPS_GetKernelRunlevel() == KR_READY) {
            // Check configuration
            $this->ValidateConfiguration();
            if (IPS_GetInstance($this->InstanceID)['InstanceStatus'] == 102) {
                // Set timer
                $this->SetSwitchDevicesOnTimer();
                $this->SetSwitchDevicesOffTimer();
                // Create Links
                $this->CreateLinks();
                // Set buffer
                $this->SetDevicesBuffer();
                // Set automatic switch
                SetValue($this->GetIDForIdent('Automatic'), $this->ReadPropertyBoolean('UseAutomatic'));
                $this->CheckState(0);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        switch ($Message) {
            case IPS_KERNELMESSAGE:
                if ($Data[0] == KR_READY) {
                    $this->ApplyChanges();
                }
                break;
            case VM_UPDATE:
                $this->CheckState($SenderID);
        }
    }

    public function GetConfigurationForm()
    {
        $formdata = json_decode(file_get_contents(__DIR__ . '/form.json'));
        $devices = json_decode($this->ReadPropertyString('DeviceList'));
        if (!empty($devices)) {
            $status = true;
            foreach ($devices as $currentKey => $currentArray) {
                $rowColor = '';
                foreach ($devices as $searchKey => $searchArray) {
                    // Search for duplicate entries
                    if ($searchArray->Position == $currentArray->Position) {
                        if ($searchKey != $currentKey) {
                            $rowColor = '#FFC0C0';
                            $status = false;
                        }
                    }
                }
                // Check entries
                if (($currentArray->Position == '') || ($currentArray->Description == '') || ($currentArray->DeviceID == 0)) {
                    $rowColor = '#FFC0C0';
                    $status = false;
                }
                $formdata->elements[24]->values[] = ['rowColor' => $rowColor];
                if ($status == false) {
                    $this->SetStatus(2511);
                }
            }
        }
        return json_encode($formdata);
    }

    //#################### Request action

    public function RequestAction($Ident, $Value)
    {
        try {
            switch ($Ident) {
                case 'Devices':
                    $this->SwitchDevices($Value);
                    break;
                case 'Automatic':
                    $this->SetAutomatic($Value);
                    break;
                default:
                    throw new Exception('Invalid Ident');
            }
        } catch (Exception $e) {
            IPS_LogMessage('UBHMIT', $e->getMessage());
        }
    }

    //#################### Public

    /**
     * Switch assigned devices.
     *
     * @param bool $State
     */
    public function SwitchDevices(bool $State)
    {
        $this->SetBuffer('SwitchingMode', json_encode(['mode' => $State]));
        $devices = json_decode($this->GetBuffer('Devices'), true);
        if (!empty($devices)) {
            if ($this->ReadPropertyInteger('DeviceSwitchingDelay') == 0) {
                foreach ($devices as $device) {
                    $this->ToggleDevice($device, $State);
                }
                $this->SetSwitchDevicesOnTimer();
                $this->SetSwitchDevicesOffTimer();
                $this->SetTimerInterval('SwitchNextDevice', 0);
            } else {
                $device = $devices[1];
                // Switch device
                $this->ToggleDevice($device, $State);
                $count = count($devices);
                if ($count > 1) {
                    array_shift($devices);
                    $newDevices = array_combine(range(1, count($devices)), array_values($devices));
                    $data = json_encode($newDevices);
                    $this->SetBuffer('Devices', $data);
                    $interval = $this->ReadPropertyInteger('DeviceSwitchingDelay');
                } else {
                    $interval = 0;
                    // Reset Devices
                    $this->SetDevicesBuffer();
                    // Next timer run
                    $this->SetSwitchDevicesOnTimer();
                    $this->SetSwitchDevicesOffTimer();
                    // Reset switching mode
                    $this->SetBuffer('SwitchingMode', json_encode([]));
                }
                $this->SetTimerInterval('SwitchNextDevice', $interval);
            }
        }
    }

    /**
     * Switch next device.
     */
    public function SwitchNextDevice()
    {
        $mode = json_decode($this->GetBuffer('SwitchingMode'), true);
        if (!empty($mode)) {
            $state = $mode['mode'];
            $this->SwitchDevices($state);
        } else {
            $this->SetTimerInterval('SwitchNextDevice', 0);
        }
    }

    /**
     * Set automatic mode.
     *
     * @param bool $State
     */
    public function SetAutomatic(bool $State)
    {
        SetValue($this->GetIDForIdent('Automatic'), $State);
        IPS_SetProperty($this->InstanceID, 'UseAutomatic', $State);
        if (IPS_HasChanges($this->InstanceID)) {
            IPS_ApplyChanges($this->InstanceID);
        }
        $this->SetSwitchDevicesOnTimer();
        $this->SetSwitchDevicesOffTimer();
    }

    //#################### Protected

    /**
     * Validates the configuration.
     */
    protected function ValidateConfiguration()
    {
        $this->SetStatus(102);

        // Check random switch delay
        if ($this->ReadPropertyBoolean('UseSwitchOnTime') == true && $this->ReadPropertyBoolean('UseSwitchOffTime') == true) {
            $switchOnTime = json_decode($this->ReadPropertyString('SwitchOnTime'));
            $definedOnTime = $switchOnTime->hour . ':' . $switchOnTime->minute . ':' . $switchOnTime->second;
            $onTime = strtotime($definedOnTime);
            $switchOffTime = json_decode($this->ReadPropertyString('SwitchOffTime'));
            $definedTime = $switchOffTime->hour . ':' . $switchOffTime->minute . ':' . $switchOffTime->second;
            $offTime = strtotime($definedTime);
            $timeDifference = abs($offTime - $onTime) / 60;
            if ($this->ReadPropertyBoolean('UseRandomSwitchOffDelay') == true) {
                $randomDelayTime = json_decode($this->ReadPropertyInteger('SwitchOffDelay'));
                if ($timeDifference < $randomDelayTime * 2) {
                    $this->SetStatus(2451);
                }
            }
            if ($this->ReadPropertyBoolean('UseRandomSwitchOnDelay') == true) {
                $randomDelayTime = json_decode($this->ReadPropertyInteger('SwitchOnDelay'));
                if ($timeDifference < $randomDelayTime * 2) {
                    $this->SetStatus(2351);
                }
            }
        }
        // Check location
        if ($this->ReadPropertyBoolean('UseAutomatic') == true && $this->ReadPropertyBoolean('UseSwitchOffTime') == true) {
            $astroID = $this->ReadPropertyInteger('SwitchOffAstroID');
            if ($astroID != 0) {
                $parentID = IPS_GetParent($astroID);
                if (IPS_ObjectExists($parentID)) {
                    if (($parentID == 0) || ($moduleID = IPS_GetInstance($parentID)['ModuleInfo']['ModuleID'] != LOCATION_CONTROL)) {
                        $this->SetStatus(2421);
                    }
                }
            }
        }
        if ($this->ReadPropertyBoolean('UseAutomatic') == true && $this->ReadPropertyBoolean('UseSwitchOnTime') == true) {
            $astroID = $this->ReadPropertyInteger('SwitchOnAstroID');
            if ($astroID != 0) {
                $parentID = IPS_GetParent($astroID);
                if (IPS_ObjectExists($parentID)) {
                    if (($parentID == 0) || ($moduleID = IPS_GetInstance($parentID)['ModuleInfo']['ModuleID'] != LOCATION_CONTROL)) {
                        $this->SetStatus(2321);
                    }
                }
            }
        }
        // Set description
        $description = $this->ReadPropertyString('Description');
        if ($description == '') {
            $this->SetStatus(2121);
        } else {
            // Rename instance
            IPS_SetName($this->InstanceID, $description);
            // Rename Devices switch
            IPS_SetName($this->GetIDForIdent('Devices'), $this->ReadPropertyString('Description'));
        }
        // Set category
        $categoryID = $this->ReadPropertyInteger('Category');
        IPS_SetParent($this->InstanceID, $categoryID);
    }

    /**
     * Toggle HomeMatic device.
     *
     * @param int  $Device
     * @param bool $State
     */
    protected function ToggleDevice(int $Device, bool $State)
    {
        $toggle = @HM_WriteValueBoolean($Device, 'STATE', $State);
        if ($toggle == false) {
            $name = IPS_GetName($Device);
            $text = $name . ', ' . $this->Translate('could not switch device!');
            $this->WriteLogMessage($text);
        } else {
            SetValue($this->GetIDForIdent('Devices'), $State);
        }
    }

    /**
     * Create link for assigned devices.
     */
    protected function CreateLinks()
    {
        // Create new array from device list
        // key = position
        // value = target id
        $targetIDs = [];
        $devices = json_decode($this->ReadPropertyString('DeviceList'));
        if (!empty($devices)) {
            foreach ($devices as $device) {
                if ($device->UseDevice == true) {
                    $childrenIDs = IPS_GetChildrenIDs($device->DeviceID);
                    foreach ($childrenIDs as $childrenID) {
                        $objectIdent = IPS_GetObject($childrenID)['ObjectIdent'];
                        if ($objectIdent == 'STATE') {
                            $targetIDs[$device->Position] = $childrenID;
                        }
                    }
                }
            }
        }
        // Create new array from existing links
        // key = link id
        // value = existing target id
        $existingTargetIDs = [];
        $childrenIDs = IPS_GetChildrenIDs($this->InstanceID);
        foreach ($childrenIDs as $childrenID) {
            // Check if children is a link
            $objectType = IPS_GetObject($childrenID)['ObjectType'];
            if ($objectType == 6) {
                // Get target id
                $existingTargetID = IPS_GetLink($childrenID)['TargetID'];
                $existingTargetIDs[$childrenID] = $existingTargetID;
            }
        }
        // Delete dead links
        $deadLinks = array_diff($existingTargetIDs, $targetIDs);
        foreach ($deadLinks as $linkID => $existingTargetID) {
            if (IPS_LinkExists($linkID)) {
                IPS_DeleteLink($linkID);
                $this->UnregisterMessage($existingTargetID, VM_UPDATE);
            }
        }
        // Create new links
        $newLinks = array_diff($targetIDs, $existingTargetIDs);
        foreach ($newLinks as $position => $targetID) {
            $linkID = IPS_CreateLink();
            IPS_SetParent($linkID, $this->InstanceID);
            if (!empty($position)) {
                IPS_SetPosition($linkID, $position + 4);
                IPS_SetName($linkID, $devices[$position - 1]->Description);
            }
            IPS_SetLinkTargetID($linkID, $targetID);
            $this->RegisterMessage($targetID, VM_UPDATE);
        }
        // Edit existing links
        $existingLinks = array_intersect($existingTargetIDs, $targetIDs);
        foreach ($existingLinks as $linkID => $targetID) {
            $position = array_search($targetID, $targetIDs);
            if (!empty($position)) {
                IPS_SetPosition($linkID, $position + 4);
                IPS_SetName($linkID, $devices[$position - 1]->Description);
            }
            $this->RegisterMessage($targetID, VM_UPDATE);
        }
    }

    /**
     * Set devices buffer.
     */
    protected function SetDevicesBuffer()
    {
        $devices = json_decode($this->ReadPropertyString('DeviceList'));
        $buffer = [];
        if (!empty($devices)) {
            foreach ($devices as $device) {
                if ($device->UseDevice == true) {
                    $buffer[$device->Position] = (string) $device->DeviceID;
                }
            }
        }
        ksort($buffer);
        $this->SetBuffer('Devices', json_encode($buffer));
    }

    /**
     *  Set the timer for switching the devices on.
     */
    protected function SetSwitchDevicesOnTimer()
    {
        $timerInterval = 0;
        $timerInfo = '';
        if ($this->ReadPropertyBoolean('UseAutomatic') == true) {
            $now = time();
            if ($this->ReadPropertyBoolean('UseSwitchOnTime') == true) {
                // Astro
                $astroID = $this->ReadPropertyInteger('SwitchOnAstroID');
                if ($astroID != 0) {
                    $timestamp = GetValueInteger($astroID);
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                } else {
                    // Timer
                    $switchOnTime = json_decode($this->ReadPropertyString('SwitchOnTime'));
                    $hour = $switchOnTime->hour;
                    $minute = $switchOnTime->minute;
                    $second = $switchOnTime->second;
                    $definedTime = $hour . ':' . $minute . ':' . $second;
                    if (time() >= strtotime($definedTime)) {
                        $timestamp = mktime($hour, $minute, $second, date('n'), date('j') + 1, date('Y'));
                    } else {
                        $timestamp = mktime($hour, $minute, $second, date('n'), date('j'), date('Y'));
                    }
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                }
                // Check random delay
                if ($this->ReadPropertyBoolean('UseRandomSwitchOnDelay') == true) {
                    $switchOnDelay = $this->ReadPropertyInteger('SwitchOnDelay');
                    if ($timerInterval != 0 && $switchOnDelay > 0) {
                        $delay = rand(0, $switchOnDelay * 60000) * 2 - $switchOnDelay * 60000;
                        $timerInterval = $timerInterval + $delay;
                        $timerInfo += $delay / 1000;
                    }
                }
            }
        }
        // Set timer
        $this->SetTimerInterval('SwitchDevicesOn', $timerInterval);
        // Set next switch on time info
        $date = '';
        if (!empty($timerInfo)) {
            $date = gmdate('d.m.Y, H:i:s', $timerInfo);
        }
        $this->SetValue('NextSwitchOnTime', $date);
    }

    /**
     * Set the timer for switching the devices off.
     */
    protected function SetSwitchDevicesOffTimer()
    {
        $timerInterval = 0;
        $timerInfo = '';
        if ($this->ReadPropertyBoolean('UseAutomatic') == true) {
            $now = time();
            if ($this->ReadPropertyBoolean('UseSwitchOffTime') == true) {
                // Astro
                $astroID = $this->ReadPropertyInteger('SwitchOffAstroID');
                if ($astroID != 0) {
                    $timestamp = GetValueInteger($astroID);
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                } else {
                    // Timer
                    $switchOffTime = json_decode($this->ReadPropertyString('SwitchOffTime'));
                    $hour = $switchOffTime->hour;
                    $minute = $switchOffTime->minute;
                    $second = $switchOffTime->second;
                    $definedTime = $hour . ':' . $minute . ':' . $second;
                    if (time() >= strtotime($definedTime)) {
                        $timestamp = mktime($hour, $minute, $second, date('n'), date('j') + 1, date('Y'));
                    } else {
                        $timestamp = mktime($hour, $minute, $second, date('n'), date('j'), date('Y'));
                    }
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                }
                // Check random delay
                if ($this->ReadPropertyBoolean('UseRandomSwitchOffDelay') == true) {
                    $switchOffDelay = $this->ReadPropertyInteger('SwitchOffDelay');
                    if ($timerInterval != 0 && $switchOffDelay > 0) {
                        $delay = rand(0, $switchOffDelay * 60000) * 2 - $switchOffDelay * 60000;
                        $timerInterval = $timerInterval + $delay;
                        $timerInfo += $delay / 1000;
                    }
                }
            }
        }
        // Set timer
        $this->SetTimerInterval('SwitchDevicesOff', $timerInterval);
        // Set next switch off time info
        $date = '';
        if (!empty($timerInfo)) {
            $date = gmdate('d.m.Y, H:i:s', $timerInfo);
        }
        $this->SetValue('NextSwitchOffTime', $date);
    }

    /**
     * Set the state of the devices switch.
     *
     * @param int $SenderID
     */
    protected function CheckState(int $SenderID)
    {
        $switchState = false;
        $senderState = false;
        if ($SenderID != 0) {
            $senderState = GetValue($SenderID);
        }
        if ($senderState == true) {
            $switchState = true;
        } else {
            $devices = json_decode($this->ReadPropertyString('DeviceList'));
            if (!empty($devices)) {
                foreach ($devices as $device) {
                    if ($device->UseDevice == true) {
                        $childrenIDs = IPS_GetChildrenIDs($device->DeviceID);
                        foreach ($childrenIDs as $childrenID) {
                            $objectIdent = IPS_GetObject($childrenID)['ObjectIdent'];
                            if ($objectIdent == 'STATE') {
                                if (GetValue($childrenID) == true) {
                                    $switchState = true;
                                }
                            }
                        }
                    }
                }
            }
        }
        SetValue($this->GetIDForIdent('Devices'), $switchState);
    }

    /**
     * Logs a message.
     *
     * @param string $Text
     */
    protected function WriteLogMessage(string $Text)
    {
        IPS_LogMessage('UBHMIT', 'ID: ' . $this->InstanceID . ', ' . $Text);
        $webFront = IPS_GetInstanceListByModuleID(WEBFRONT_GUID)[0];
        WFC_SendNotification($webFront, $this->Translate('Error'), $Text, 'Warning', 10);
    }
}
