<?php

abstract class Enumeration {

  public function value() {
    return $this->mVal;
  }

  public function __toString() {
    return static::$aValues[$this->mVal];
  }

  protected function __construct($mVal) {
    if (!isset(static::$aValues[$mVal])) {
      throw new UnexpectedValueException($mVal);
    }
    $this->mVal = $mVal;
  }

  protected $mVal;
}

trait EnumerationFactory {
  public static function create($mVal) {
    return new self($mVal);    
  }

  public static function fromString($sEnum) {
    if (null == self::$aValuesFlip) {
      self::$aValuesFlip = array_flip(static::$aValues);
      if (!isset(self::$aValuesFlip[$sEnum])) {
        throw new UnexpectedValueException($sEnum);
      }
      return self::create(self::$aValuesFlip[$sEnum]);
    }
  }

  protected static $aValuesFlip = null;
}

class EnvDamageType extends Enumeration {

  const
    ENV_DMG_NONE  = 0,
    ENV_DMG_HEAT  = 1,
    ENV_DMG_ELEC  = 2,
    ENV_DMG_CRUSH = 3,
    ENV_DMG_TOXIC = 4,
    ENV_DMG_RAD   = 5
  ;

  use EnumerationFactory;

  protected static $aValues = [
    self::ENV_DMG_NONE   => 'ENV_DMG_NONE',
    self::ENV_DMG_HEAT   => 'ENV_DMG_HEAT',
    self::ENV_DMG_ELEC   => 'ENV_DMG_ELEC',
    self::ENV_DMG_CRUSH  => 'ENV_DMG_CRUSH',
    self::ENV_DMG_TOXIC  => 'ENV_DMG_TOXIC',
    self::ENV_DMG_RAD    => 'ENV_DMG_RAD'
  ];

}

class LiftPosition extends Enumeration {
  const
    POS_TOP    = 0,
    POS_BOTTOM = 1
  ;

  use EnumerationFactory;
  
  protected static $aValues = [
    self::POS_TOP    => 'POS_TOP',
    self::POS_BOTTOM => 'POS_BOTTOM',
  ];

  protected static $aValuesFlip = null;

}

class LiftBlocked extends Enumeration {
  const
    BLOCKED_STOP    = 0,
    BLOCKED_REVERSE = 1,
    BLOCKED_CRUSH   = 2
  ;

  use EnumerationFactory;
  
  protected static $aValues = [
    self::BLOCKED_STOP    => 'BLOCKED_STOP',
    self::BLOCKED_REVERSE => 'BLOCKED_REVERSE',
    self::BLOCKED_CRUSH   => 'BLOCKED_CRUSH'
  ];

}