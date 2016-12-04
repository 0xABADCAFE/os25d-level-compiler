<?php


/**
 * ZoneFlatDefinitionValidator
 *
 * Common floor/ceiling definition validation.
 */ 

abstract class ZoneFlatDefinitionValidator extends ZoneDataValidator implements ISingleZoneValidator {
  
  protected function validateFlat(stdClass $oFlat, $sMsg) {
    if (
      !isset($oFlat->baseHeight) ||
      !is_float($oFlat->baseHeight)
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid baseHeight');
    }

    if (
      isset($this->oCommon->offset->z) &&
      is_float($this->oCommon->offset->z)
    ) {
      $oFlat->baseHeight += $this->oCommon->offset->z;
    }

    $this->assertRange($oFlat->baseHeight, $sMsg . 'baseHeight');
    $oFlat->baseHeight = $this->limitPrecision(
      $oFlat->baseHeight,
      $sMsg . 'discarding excess precision in baseHeight'
    );
  }
}

/**
 * ZoneFloorDefinitionValidator
 *
 * Validates the floor definition of a zone.
 */ 

class ZoneFloorDefinitionValidator extends ZoneFlatDefinitionValidator {
  public function validate(stdClass $oZone) {
    if (
      !isset($oZone->floor)
    ) {
      throw new MissingRequiredEntityException("Zone {$oZone->runtimeId} floor definition missing");
    }
    $this->validateFlat($oZone->floor, "Zone {$oZone->runtimeId} floor ");
    $this->oLog->debug("Zone {$oZone->runtimeId} floor OK");
  }
}

/**
 * ZoneCeilingDefinitionValidator
 *
 * Validates the ceiling definition of a zone.
 */ 

class ZoneCeilingDefinitionValidator extends ZoneFlatDefinitionValidator {
  public function validate(stdClass $oZone) {
    if (
      !isset($oZone->ceiling)
    ) {
      throw new MissingRequiredEntityException("Zone {$oZone->runtimeId} ceiling definition missing");
    }
    $this->validateFlat($oZone->ceiling, "Zone {$oZone->runtimeId} ceiling ");
    $this->oLog->debug("Zone {$oZone->runtimeId} ceiling OK");
  }
}

