<?php

/**
 * ZoneSetGeometryValidator
 *
 * Validates the geometry of a zones in a zones fie
 */ 

class ZoneSetGeometryValidator extends ZoneDataValidator implements IZoneSetValidator {

  public function validate(stdClass $oZoneData) {
    foreach ($oZoneData->zoneList as $oZone) {
      $this->validateVerticalLimits($oZone);
      $this->validateIsConvexCounterClockwise($oZone);
    }
  }

  private function validateVerticalLimits(stdClass $oZone) {
    if ($oZone->ceiling->baseHeight <= $oZone->floor->baseHeight) {    
      throw new IllegalZoneGeometryException("Zone {$oZone->runtimeId} ceiling baseHeight must be greater than floor");       
    }
  }

  private function validateIsConvexCounterClockwise(stdClass $oZone) {
    $oBounds = (object)[
      'min' => (object)[
        'x' => self::F_MAX_ORDINATE,
        'y' => self::F_MAX_ORDINATE,
        'z' => $oZone->floor->baseHeight
      ],
      'max' => (object)[
        'x' => self::F_MIN_ORDINATE,
        'y' => self::F_MIN_ORDINATE,
        'z' => $oZone->ceiling->baseHeight
      ]
    ];

    $iN = count($oZone->points);
    for ($i0 = 0; $i0 < $iN; $i0++) {
      $oBounds->min->x = min($oBounds->min->x, $oZone->points[$i0][0]);
      $oBounds->min->y = min($oBounds->min->y, $oZone->points[$i0][1]);
      $oBounds->max->x = max($oBounds->max->x, $oZone->points[$i0][0]);
      $oBounds->max->y = max($oBounds->max->y, $oZone->points[$i0][1]);

      $i1 = ($i0 + 1) % $iN;
      $i2 = ($i0 + 2) % $iN;
      $fX1 = $oZone->points[$i2][0] - $oZone->points[$i1][0];
      $fY1 = $oZone->points[$i2][1] - $oZone->points[$i1][1];
      $fX2 = $oZone->points[$i0][0] - $oZone->points[$i1][0];
      $fY2 = $oZone->points[$i0][1] - $oZone->points[$i1][1];
      $fCross = $fX1*$fY2 - $fY1*$fX2;
      if ($fCross < 0) {
        throw new IllegalZoneGeometryException("Zone {$oZone->runtimeId} Polygon is not convex/counter-clockwise");
      }
    }
    $this->oLog->debug("Zone {$oZone->runtimeId} Polygon geometry OK");
    
    // Add the bounding box to the representation
    $oZone->bounds = $oBounds;
  }
}

