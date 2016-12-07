<?php

/**
 * Basic Limits for Zones
 */
interface IZoneLimits {
  const
    // Editor space axis limits are -325.00 to 325.00, 2dp
    F_MIN_ORDINATE = -325.0,
    F_MAX_ORDINATE = 325.0,
    I_PRECISION    = 2,

    // Points per zone limits
    I_MIN_POINTS   = 3,
    I_MAX_POINTS   = 16,
    
    // Map space axis limits are uint16, centred on 32768
    F_SCALE        = 100,
    I_BIAS         = 32768
  ;      
}

/**
 * ZoneUtil class. Helper utility class for Zone tasks.
 *
 */
class ZoneUtil implements IZoneLimits {
  public static function intOrdinate($fVal) {
    return self::I_BIAS + (int)($fVal * self::F_SCALE);
  }
}

/**
 * BoundingBox class. Simple, axis aligned bounding box type that takes the .
 */
class BoundingBox {
  public
    $iMinX,
    $iMinY,
    $iMinZ,
    $iMaxX,
    $iMaxY,
    $iMaxZ
  ;
  
  public function __construct(stdClass $oBox) {
    $this->iMinX = ZoneUtil::intOrdinate($oBox->min->x);
    $this->iMinY = ZoneUtil::intOrdinate($oBox->min->y);
    $this->iMinZ = ZoneUtil::intOrdinate($oBox->min->z);
    $this->iMaxX = ZoneUtil::intOrdinate($oBox->max->x);
    $this->iMaxY = ZoneUtil::intOrdinate($oBox->max->y);
    $this->iMaxZ = ZoneUtil::intOrdinate($oBox->max->z);
  }

  public function contacts(BoundingBox $oBox) {
    return !(
      $this->iMaxX < $oBox->iMinX ||
      $this->iMinX > $oBox->iMaxX ||
      $this->iMaxY < $oBox->iMinY ||
      $this->iMinY > $oBox->iMaxY ||
      $this->iMaxZ < $oBox->iMinZ ||
      $this->iMinZ > $oBox->iMaxZ
    );
  
  }

  public function describe() {
    return sprintf(
      "[%5d, %5d, %5d], [%5d, %5d, %5d]",
      $this->iMinX, $this->iMinY, $this->iMinZ,
      $this->iMaxX, $this->iMaxY, $this->iMaxZ
    );
  }
}

/**
 * Basic Zone container class. Represents the end stage of validation and parsing. The JSON representation
 * is wrapped up and all the other required properties are determined
 */
class Zone implements IZoneLimits, IBinaryExportable {

  private
    $oJRep      = null,
    $oBBox      = null,
    $iFloorBase = 0,
    $iFloorExt  = 0,
    $iCeilBase  = 0,
    $iCeilExt   = 0,
    $aOrds      = [],
    $aEdges     = [],
    $aEdgesRev  = [],
    $aTestZones = []
  ;
  
  public function __construct(stdClass $oJRep) {
    $this->oJRep = $oJRep;
    $this->oBBox = new BoundingBox($this->oJRep->bounds);
    $this->buildOrdinates();
    $this->buildEdges();
    $this->buildFlats();
  }

  public function getBinaryData() {
  
    $sData = pack(
      'n*',
      // Unique ID, 0-1999
      $this->getRuntimeId(),

      // Number of points, 3-16
      count($this->aOrds),

      // Area Bounds      
      $this->oBBox->iMinX,
      $this->oBBox->iMaxX,
      $this->oBBox->iMinY,
      $this->oBBox->iMaxY,
      
      // Floor
      $this->iFloorBase,
      $this->iFloorExt,
      
      // Ceiling
      $this->iCeilBase,
      $this->iCeilExt
    ); 

    foreach ($this->aOrds as $iOrd) {
      $sData .= pack('n', $iOrd);
    }
    return $sData;
  }
  
  public function getBinaryIdent() {
    return 'ZoneData';
  }

  /**
   * Adds a Zone to an internal list that will be tested to see if they share edges with the current Zone.
   * Adding the Zone to itself will result in an exception.
   *
   * @param Zone $oZone
   * @throws InvalidArgumentException
   */
  public function addZoneForContactTest(Zone $oZone) {
    if ($this->getRuntimeId() == $oZone->getRuntimeId()) {
      throw new InvalidArgumentException();
    }
    $this->aTestZones[$oZone->getRuntimeId()] = $oZone;  
    return $this;
  }

  /**
   * Return the set of Zones that were added for contact testing.
   *
   * @return Zone[]
   */  
  public function &getZonesForContactTest() {
    return $this->aTestZones;
  }

  /**
   * Return the CCW defined set of edges in the current Zone
   *
   * @return Zone[]
   */  
  public function &getEdges() {
    return $this->aEdges;
  }

  /**
   * Return the CCW defined set of edges in the current Zone, with their ordinates reversed. Used in the
   * antiparallel edge comparison.
   *
   * @return Zone[]
   */  
  public function &getEdgesRev() {
    return $this->aEdgesRev;
  }

  public function getRuntimeId() {
    return $this->oJRep->runtimeId;
  }

  public function describe() {
    return sprintf(
      "Zone %d\n\tInfo: %s\n\tBounds:%s\n\tPoints: %s",
      $this->oJRep->runtimeId,
      $this->oJRep->comment,
      $this->oBBox->describe(),
      json_encode($this->oJRep->points)
    );
  }

  public function getBoundingBox() {
    return $this->oBBox;
  }

  public function getIdent() {
    static $i=0;
    return $i++;
  }

  private function buildOrdinates() {
    foreach($this->oJRep->points as &$tPoint) {
      $this->aOrds[] = ZoneUtil::intOrdinate($tPoint[0]);
      $this->aOrds[] = ZoneUtil::intOrdinate($tPoint[1]);
    }  
  }

  private function buildEdges() {
    $iN = count($this->aOrds);
    $iE = 1; // Enumerated 1-16
    for ($i = 0; $i < $iN; $i += 2, $iE++) {
      $j = ($i + 2) % $iN;
      $sKey = sprintf(
        "%04X:%04X:%04X:%04X",
        $this->aOrds[$i],
        $this->aOrds[$i + 1],
        $this->aOrds[$j],
        $this->aOrds[$j + 1]
      );
      $this->aEdges[$sKey] = $iE;
      $sKey = sprintf(
        "%04X:%04X:%04X:%04X",
        $this->aOrds[$j],
        $this->aOrds[$j + 1],
        $this->aOrds[$i],
        $this->aOrds[$i + 1]
      );
      $this->aEdgesRev[$sKey] = $iE;
    }
  }

  private function buildFlats() {
    $this->iFloorBase = ZoneUtil::intOrdinate($this->oJRep->floor->baseHeight);

    if (isset($this->oJRep->floor->liftInfo)) {
      $this->iFloorExt  = ZoneUtil::intOrdinate($this->oJRep->floor->liftInfo->extHeight);
    } else {
      $this->iFloorExt = $this->iFloorBase;
    }
    
    $this->iCeilBase = ZoneUtil::intOrdinate($this->oJRep->ceiling->baseHeight);
    
    if (isset($this->oJRep->ceiling->liftInfo)) {
      $this->iCeilExt = ZoneUtil::intOrdinate($this->oJRep->ceiling->liftInfo->extHeight);
    } else {
      $this->iCeilExt = $this->iCeilBase;
    }
  }
}

