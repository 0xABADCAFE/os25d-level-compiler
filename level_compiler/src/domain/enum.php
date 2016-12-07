<?php

abstract class Enumeration {

  public function value() {
    return $this->mVal;
  }

  public function __toString() {
    return static::$aValues[$this->mVal];
  }

  public static function fromString($sEnum) {
    if (null == static::$aValuesFlip) {
      static::$aValuesFlip = array_flip(static::$aValues);
      if (!isset(static::$aValuesFlip[$sEnum])) {
        throw new UnexpectedValueException($sEnum);
      }
      return static::create(static::$aValuesFlip[$sEnum]);
    }
  }

  protected function __construct($mVal) {
    if (!isset(static::$aValues[$mVal])) {
      throw new UnexpectedValueException($mVal);
    }
    $this->mVal = $mVal;
  }

  protected $mVal;
}

class DamageType extends Enumeration {

  const
    DAMAGE_NONE = 0,
    DAMAGE_HEAT = 1,
    DAMAGE_ELEC = 2
  ;

  public static function create($mVal) {
    return new self($mVal);    
  }  

  protected static $aValues = [
    self::DAMAGE_NONE => 'DAMAGE_NONE',
    self::DAMAGE_HEAT => 'DAMAGE_HEAT',
    self::DAMAGE_ELEC => 'DAMAGE_ELEC',
  ];

  protected static $aValuesFlip = null;
}

class LiftPosition extends Enumeration {
  const
    POS_TOP    = 0,
    POS_BOTTOM = 1
  ;

  public static function create($mVal) {
    return new self($mVal);    
  }  

  protected static $aValues = [
    self::POS_TOP    => 'POS_TOP',
    self::POS_BOTTOM => 'POS_BOTTOM',
  ];

  protected static $aValuesFlip = null;

}
