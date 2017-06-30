<?php


/**
 * ZoneFlatDefinitionValidator
 *
 * Common floor/ceiling definition validation.
 */

abstract class ZoneFlatDefinitionValidator extends ZoneDataValidator implements ISingleZoneValidator {

  use TEnvDamageValidator;
  use TZoneScaling;

  protected function validateFlat(stdClass $oFlat, string $sMsg) {
    if (
      !isset($oFlat->baseHeight) ||
      !is_float($oFlat->baseHeight)
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid baseHeight');
    }

    $fOffsetZ = 0;

    if (
      isset($this->oCommon->offset->z) &&
      is_float($this->oCommon->offset->z)
    ) {
      $fOffsetZ = $this->oCommon->offset->z;
      $oFlat->baseHeight += $fOffsetZ;
    }

    $this->assertRange($oFlat->baseHeight, $sMsg . 'baseHeight');
    $oFlat->baseHeight = $this->limitPrecision(
      $oFlat->baseHeight,
      $sMsg . 'discarding excess precision in baseHeight'
    );
    if (isset($oFlat->liftInfo)) {
      $this->validateLiftInfo($oFlat, $fOffsetZ, $sMsg);
    }

    if (isset($oFlat->contactDamage)) {
      $this->validateEnvDamage($oFlat->contactDamage, $sMsg);
      $this->oLog->debug($sMsg . "contactDamage OK");
    }
  }

  protected function validateLiftInfo(stdClass $oFlat, float $fOffsetZ, string $sMsg) {
    $oLift = $oFlat->liftInfo;
    if (
      !isset($oLift->extHeight) ||
      !is_float($oLift->extHeight)
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid extHeight');
    }

    $oLift->extHeight += $fOffsetZ;

    $this->assertRange($oLift->extHeight, $sMsg . 'extHeight');
    $oLift->extHeight = $this->limitPrecision(
      $oLift->extHeight,
      $sMsg . 'discarding excess precision in extHeight'
    );

    $fMinSpeed = 1.0 / self::F_SCALE;

    if (
      !isset($oLift->raiseSpeed) ||
      !is_float($oLift->raiseSpeed) ||
      $oLift->raiseSpeed < $fMinSpeed
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid raiseSpeed');
    }

    $oLift->raiseSpeed = $this->limitPrecision(
      $oLift->raiseSpeed,
      $sMsg . 'discarding excess precision in raiseSpeed'
    );

    if (
      !isset($oLift->lowerSpeed) ||
      !is_float($oLift->lowerSpeed) ||
      $oLift->lowerSpeed < $fMinSpeed
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid lowerSpeed');
    }

    $oLift->lowerSpeed = $this->limitPrecision(
      $oLift->lowerSpeed,
      $sMsg . 'discarding excess precision in lowerSpeed'
    );

    if (
      !isset($oLift->initPos) ||
      !is_string($oLift->initPos)
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid initPos');
    }
    $oLift->iInitPos = LiftPosition::fromString($oLift->initPos)->value();

    if (
      !isset($oLift->blocked) ||
      !is_string($oLift->blocked)
    ) {
      throw new MissingRequiredEntityException($sMsg . 'missing or invalid blocked');
    }
    $oLift->iBlocked = LiftBlocked::fromString($oLift->blocked)->value();


    // TODO - triggers

    $this->oLog->debug($sMsg . "lift definition OK");
  }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

