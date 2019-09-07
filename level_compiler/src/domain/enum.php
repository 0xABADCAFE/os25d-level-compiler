<?php

/**
 * Enumeration of environmental hazards
 */
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Enumeration of lift/crusher positions
 */
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
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Enumeration of lift/crusher block behaviours
 */
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

