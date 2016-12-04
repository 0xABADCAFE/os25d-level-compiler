<?php

/////////////////////////////////////////////////////////////////////////////

interface IZoneLimits {
  const
    F_MIN_ORDINATE = -325.0,
    F_MAX_ORDINATE = 325.0,
    I_MIN_POINTS   = 3,
    I_MAX_POINTS   = 16
  ;      
}

interface IZoneHeaderInfo {
  const
    S_TYPE     = 'ZoneData',
    I_VERSION  = 1
  ;
}

/////////////////////////////////////////////////////////////////////////////

abstract class ZoneValidator {
  public function __construct(Logger $oLogger) {
    $this->oLogger = $oLogger;
  }
  
  public abstract function validate(stdClass $oZoneData);

  protected $oLogger;
}

/////////////////////////////////////////////////////////////////////////////

class ZoneSetHeaderValidator extends ZoneValidator implements IZoneHeaderInfo {
  public function validate(stdClass $oZoneData) {
    // check fields exist
    if (
      !isset($oZoneData->headerInfo) ||
      !isset($oZoneData->headerInfo->type) ||
      !isset($oZoneData->headerInfo->version)
    ) {
      $this->oLogger->warn("Missing or incomplete headerInfo structure");
      throw new MissingRequiredEntityException();
    }
    $this->oLogger->debug("Full headerInfo section exists, checking");
    // check field types
    if (
      $oZoneData->headerInfo->type != self::S_TYPE ||
      !preg_match("/^\d+\.\d+$/", $oZoneData->headerInfo->version)
    ) {
      $this->oLogger->warn("HeaderInfo field data invalid");
      throw new InvalidZoneDataException();
    }
    
    // check actual version
    list ($iVersion, $iRevision) = sscanf($oZoneData->headerInfo->version, "%d.%d");
    if ($iVersion > self::I_VERSION) {
      $this->oLogger->warn("headerInfo version over that supported");    
      throw new UnsupportedZoneVersionException();
    }
    $this->oLogger->debug("Header OK, version " . $oZoneData->headerInfo->version);
  }
}

/////////////////////////////////////////////////////////////////////////////

class ZoneSetDefinitionValidator extends ZoneValidator implements IZoneLimits {
  public function validate(stdClass $oZoneData) {
    // assign each visited Zone a unique runtime identifier
    static $iNextId = 0;
    foreach ($oZoneData->zoneList as $i => $oZone) {
      $oZone->runtimeId = ++$iNextId;
      try {
        $this->validateFloor($oZone);
        $this->validateCeiling($oZone);
        $this->validatePoints($oZone);
      } catch (Exception $e) {
        $this->oLogger->warn("Caught " . get_class($e) . " when processing Zone {$oZone->runtimeId}");
        unset($oZoneData->zoneList[$i]);
      }
    }
  }

  private function validateFloor(stdClass $oZone) {
    if (
      !isset($oZone->floor)
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} floor definition missing");
      throw new MissingRequiredEntityException();
    }
    $this->validateFlat($oZone->floor);  
  }
  
  private function validateCeiling(stdClass $oZone) {
    if (
      !isset($oZone->ceiling)
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} ceiling definition missing");    
      throw new MissingRequiredEntityException();
    }  
    $this->validateFlat($oZone->ceiling);
    
    if ($oZone->ceiling->baseHeight <= $oZone->floor->baseHeight) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} ceiling baseHeight must be greater than floor");    
      throw new InvalidZoneDataException();       
    }
  }

  private function validateFlat(stdClass $oFlat) {
    if (
      !isset($oFlat->baseHeight) ||
      !is_float($oFlat->baseHeight)
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} missing or invalid baseHeight");    
      throw new MissingRequiredEntityException();
    }  
    if (
      $oFlat->baseHeight < self::F_MIN_ORDINATE ||
      $oFlat->baseHeight > self::F_MAX_ORDINATE
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} base height outside valid range");
      throw new InvalidZoneDatException(); 
    }    

    $fZ = round($oFlat->baseHeight, 2);
    if ($fZ != $oFlat->baseHeight) {
      $this->oLogger->notice("Zone {$oZone->runtimeId} Discarding excess precision in Z ordinate");
    }
    $oFlat->baseHeight = $fZ;

  }

  private function validatePoints(stdClass $oZone) {
    if (
      !isset($oZone->points) ||
      !is_array($oZone->points)
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} points array not found");
      throw new MissingRequiredEntityException();
    }
    $iPoints = count($oZone->points);
    if (
      $iPoints < self::I_MIN_POINTS ||
      $iPoints > self::I_MAX_POINTS
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} points array has invalid count $iPoints");
      throw new InvalidZoneDataException();
    }
    $this->oLogger->debug("Zone {$oZone->runtimeId} points array has $iPoints entries to check");
    
    // Check for degenerate/duplicate points
    $aDegenerate = [];
    foreach ($oZone->points as $i => &$tPoint) {
      $this->validatePointDef($tPoint, $i);    
      $this->validatePointRange($tPoint, $i);  
      $this->limitPointPrecision($tPoint, $i);

      $sKey = sprintf("%.2f:%.2f", $tPoint[0], $tPoint[1]);
      if (isset($aDegenerate[$sKey])) {
        $iD = $aDegenerate[$sKey];
        $this->oLogger->warn("Zone {$oZone->runtimeId} points[$i] is a duplicate of points[$iD]");      
        throw new InvalidZoneDataException();
      } else {
        $aDegenerate[$sKey] = $i;
      }
    }
  }

  private function validatePointDef(array& $tPoint, $i) {
    // Tuple structure
    if (
      count($tPoint)!=2 ||
      !is_float($tPoint[0]) ||
      !is_float($tPoint[1])
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} points[$i] has invalid count or contents");
      throw new InvalidZoneDataException();
    }
  }
  
  private function validatePointRange(array& $tPoint, $i) {
    if (
      $tPoint[0] < self::F_MIN_ORDINATE ||
      $tPoint[0] > self::F_MAX_ORDINATE ||
      $tPoint[1] < self::F_MIN_ORDINATE ||
      $tPoint[1] > self::F_MAX_ORDINATE
    ) {
      $this->oLogger->warn("Zone {$oZone->runtimeId} points[$i] invalid ordinate");
      throw new InvalidZoneDataException();
    }  
  }

  private function limitPointPrecision(array& $tPoint, $i) {
    $fX = round($tPoint[0], 2);
    $fY = round($tPoint[1], 2);
    if ($fX != $tPoint[0]) {
      $this->oLogger->notice("Zone {$oZone->runtimeId} Discarding excess precision in points[$i] X ordinate");
    }
    $tPoint[0] = $fX;
    if ($fY != $tPoint[1]) {
      $this->oLogger->notice("Zone {$oZone->runtimeId} Discarding excess precision in points[$i] Y ordinate");      
    }
    $tPoint[1] = $fY;
  }
}

class ZoneSetGeometryValidator extends ZoneValidator {

  public function validate(stdClass $oZoneData) {
    foreach ($oZoneData->zoneList as $oZone) {
      $this->validateIsConvexCounterClockwise($oZone);
    }
  }

  private function validateIsConvexCounterClockwise(stdClass $oZone) {
    $iN = count($oZone->points);
    for ($i0 = 0; $i0 < $iN; $i0++) {
      $i1 = ($i0 + 1) % $iN;
      $i2 = ($i0 + 2) % $iN;
      $fX1 = $oZone->points[$i2][0] - $oZone->points[$i1][0];
      $fY1 = $oZone->points[$i2][1] - $oZone->points[$i1][1];
      $fX2 = $oZone->points[$i0][0] - $oZone->points[$i1][0];
      $fY2 = $oZone->points[$i0][1] - $oZone->points[$i1][1];
      $fCross = $fX1*$fY2 - $fY1*$fX2;
      if ($fCross < 0) {
        $this->oLogger->warn("Zone {$oZone->runtimeId} Polygon is not convex/counter-clockwise");
        throw new IllegalZoneGeometryException();
      }
    }
    $this->oLogger->debug("Zone {$oZone->runtimeId} Polygon convex and counter-clockwise");
  }
}
