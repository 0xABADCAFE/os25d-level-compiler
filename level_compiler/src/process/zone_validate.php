<?php

/**
 * ZoneDataValidator
 *
 * Base validator class for verification of Zone data.
 */

abstract class ZoneDataValidator implements IZoneLimits {

  public function __construct(ILog $oLog) {
    $this->oLog = $oLog;
  }

  public function setCommon(stdClass $oCommon = null) {
    $this->oCommon = $oCommon;
  }
  
  /**
   * Main validation entry point. Validates part of the overall JSON data.
   *
   * @param stdClass $oZoneData
   * @return void
   * @throws ZoneValidationException
   */
  public abstract function validate(stdClass $oZoneData);

  /**
   * Utility function for rounding raw floating point to the precision set in
   * IZoneLimits::I_PRECISION
   *
   * @param float $fRaw
   * @param string $sMsg
   * @return float
   */
  protected function limitPrecision($fRaw, $sMsg = 'Limited precision') {
    $fRound = round($fRaw, self::I_PRECISION);
    if ($fRound != $fRaw) {
      $this->oLog->notice(sprintf('%s [%f -> %f]', $sMsg, $fRaw, $fRound));
    }
    return $fRound;
  }

  /**
   * Utility function for asserting that an Editor space ordinate is within
   * IZoneLimits::F_MIN_ORDINATE to IZoneLimits::F_MAX_ORDINATE
   *
   * @param float $fRaw
   * @param string $sMsg
   * @return void
   * @throws InvalidZoneDataException
   */

  protected function assertRange($fOrdinate, $sMsg) {
    if (
      $fOrdinate < self::F_MIN_ORDINATE ||
      $fOrdinate > self::F_MAX_ORDINATE
    ) {
      throw new InvalidZoneDataException(sprintf(
        "%s [%.2f not in range %.2f %.2f]",
        $sMsg,
        $fOrdinate,
        self::F_MIN_ORDINATE,
        self::F_MAX_ORDINATE
      )); 
    }
  }

  /** @var ILog $oLog */
  protected $oLog    = null;

  /** @var stdClass $oCommon */
  protected $oCommon = null;
}

/**
 * Tagging interface for validators that operate on a single Zone
 */
interface ISingleZoneValidator {

}

/**
 * Tagging interface for validators that operate on a set of Zones
 */
interface IZoneSetValidator {

}

require_once 'zone_validators/zone_file.php';
require_once 'zone_validators/zone_flats.php';
require_once 'zone_validators/zone_points.php';
require_once 'zone_validators/zoneset_definition.php';
require_once 'zone_validators/zoneset_geometry.php';
