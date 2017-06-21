<?php

/**
 * ZonePointsDefinitionValidator
 *
 * Validates the points definition of a zone.
 */

class ZonePointsDefinitionValidator extends ZoneDataValidator implements ISingleZoneValidator {

  use TZoneScaling;

  public function validate(stdClass $oZone) {
    if (
      !isset($oZone->points) ||
      !is_array($oZone->points)
    ) {
      throw new MissingRequiredEntityException("Zone {$oZone->runtimeId} missing points array");
    }
    $iPoints = count($oZone->points);
    if (
      $iPoints < self::I_MIN_POINTS ||
      $iPoints > self::I_MAX_POINTS
    ) {
      throw new InvalidZoneDataException("Zone {$oZone->runtimeId} points array has invalid count $iPoints");
    }
    $this->oLog->debug("Zone {$oZone->runtimeId} points array has $iPoints entries to check");

    $fOffsetX = 0;
    $fOffsetY = 0;
    if (
      isset($this->oCommon->offset->x) &&
      is_float($this->oCommon->offset->x)
    ) {
      $fOffsetX = $this->oCommon->offset->x;
    }

    if (
      isset($this->oCommon->offset->y) &&
      is_float($this->oCommon->offset->y)
    ) {
      $fOffsetY = $this->oCommon->offset->y;
    }

    // Check for degenerate/duplicate points
    $aDegenerate = [];
    foreach ($oZone->points as $i => &$tPoint) {
      $sMsg = "Zone {$oZone->runtimeId} points[$i] ";
      $this->validatePointDef($tPoint, $sMsg);

      $tPoint[0] += $fOffsetX;
      $tPoint[1] += $fOffsetY;

      $this->validatePointRange($tPoint, $sMsg);
      $this->limitPointPrecision($tPoint, $sMsg);

      $sKey = sprintf("%.2f:%.2f", $tPoint[0], $tPoint[1]);
      if (isset($aDegenerate[$sKey])) {
        $iD = $aDegenerate[$sKey];
        throw new InvalidZoneDataException($sMsg . "is a duplicate of points[$iD]");
      } else {
        $aDegenerate[$sKey] = $i;
      }
    }
    $this->oLog->debug("Zone {$oZone->runtimeId} points OK");
  }

  private function validatePointDef(array& $tPoint, string $sMsg) {
    // Tuple structure
    if (
      count($tPoint)!=2 ||
      !is_float($tPoint[0]) ||
      !is_float($tPoint[1])
    ) {
      throw new InvalidZoneDataException($sMsg . "has invalid count or contents");
    }
  }

  private function validatePointRange(array& $tPoint, string $sMsg) {
    $this->assertRange($tPoint[0], $sMsg . 'X ordinate');
    $this->assertRange($tPoint[1], $sMsg . 'Y ordinate');
  }

  private function limitPointPrecision(array& $tPoint, string $sMsg) {
    $sMsg .= 'discarding excess precision in ';
    $tPoint[0] = $this->limitPrecision($tPoint[0], $sMsg . 'X ordinate');
    $tPoint[1] = $this->limitPrecision($tPoint[1], $sMsg . 'Y ordinate');
  }
}

