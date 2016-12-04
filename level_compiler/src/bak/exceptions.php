<?php

/////////////////////////////////////////////////////////////////////////////

class ZoneValidationException extends Exception {

}

class UnsupportedZoneVersionException extends ZoneValidationException {

}

class InvalidZoneDataException extends ZoneValidationException {

}

class IllegalZoneGeometryException extends InvalidZoneDataException {

}

class MissingRequiredEntityException extends InvalidZoneDataException {

}

