<?php

/**
 * ZoneSetDefinitionValidator
 *
 * Validates the structure and zones data in a zones file.
 */ 

class ZoneSetDefinitionValidator extends ZoneDataValidator implements IZoneSetValidator {

  public function __construct(ILog $oLog) {
    $this->oLog = $oLog;
    $this->aValidators = [
      new ZoneFloorDefinitionValidator($oLog),
      new ZoneCeilingDefinitionValidator($oLog),
      new ZonePointsDefinitionValidator($oLog)
    ];
  }

  public function validate(stdClass $oZoneSet) {
  
    // check fields exist
    if (
      !isset($oZoneSet->zoneList) ||
      !is_array($oZoneSet->zoneList) ||
      !count($oZoneSet->zoneList)
    ) {
      throw new MissingRequiredEntityException("Missing or incomplete zoneList structure");
    }
    $this->oLog->debug(
      "zoneList section exists with " . count($oZoneSet->zoneList) .
      " entries, checking each entry..."
    );

    $oCommon = isset($oZoneSet->common) ? $oZoneSet->common : null;
    foreach ($this->aValidators as $oValidator) {
      $oValidator->setCommon($oCommon);
    }
    
    // assign each visited Zone a unique runtime identifier
    static $iNextId = 0;
    foreach ($oZoneSet->zoneList as $i => $oZone) {
      $oZone->runtimeId = $iNextId;
      try {
        $sDesc = isset($oZone->comment) ? $oZone->comment : 'No description';
        $this->oLog->info("Beginning definition validation of Zone {$oZone->runtimeId} ($sDesc)");
        foreach ($this->aValidators as $oValidator) {
          $oValidator->validate($oZone);
        }
        $iNextId++;
      } catch (ZoneValidationExceptionException $e) {
        $this->oLog->warn(
          "Caught unexpeted " . get_class($e) .
          " when processing Zone {$oZone->runtimeId} with message " . $e->getMessage() .
          "\nThis Zone will be excluded."
        );
        unset($oZoneData->zoneList[$i]);
      }
    }
  }
}
