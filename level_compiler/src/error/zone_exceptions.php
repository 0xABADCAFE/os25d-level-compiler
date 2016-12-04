<?php

class ZoneValidationException extends RuntimeException { }

class UnsupportedZoneVersionException extends ZoneValidationException { }

class InvalidZoneDataException extends ZoneValidationException { }

class MissingRequiredEntityException extends InvalidZoneDataException { }

class IllegalZoneGeometryException extends InvalidZoneDataException { }

class IllegalSharedEdgeCountException extends IllegalZoneGeometryException { }