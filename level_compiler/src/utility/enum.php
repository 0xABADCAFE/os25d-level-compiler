<?php

/**
 * Base class for Enumerated types
 */
abstract class Enumeration {

    public function value() {
        return $this->mVal;
    }

    public function __toString() : string {
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

/**
 * Implementation trait for creating Enumerated types
 */
trait EnumerationFactory {
    public static function create($mVal) : self {
        return new self($mVal);
    }

    public static function fromString(string $sEnum) : self {
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

