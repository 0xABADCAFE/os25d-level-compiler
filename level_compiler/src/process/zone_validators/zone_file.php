<?php

/**
 * ZoneSet File Header Definitions
 */

interface IZoneSetHeaderInfo {
  const
    S_TYPE     = 'ZoneData',
    I_VERSION  = 1
  ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * ZoneSetHeaderValidator
 *
 * Checks that the deserialised JSON data from a zones file contains the expected header.
 */

class ZoneSetHeaderValidator extends ZoneDataValidator implements IZoneSetHeaderInfo {

  /**
   * Validates the header data from a JSON decoded zones file.
   *
   * @param stdClass $oZoneSet
   * @return void
   * @throws MissingRequiredEntityException
   * @throws InvalidZoneDataException
   * @throws UnsupportedZoneVersionException
   */
  public function validate(stdClass $oZoneSet) {
    // check fields exist
    if (
      !isset($oZoneSet->headerInfo) ||
      !isset($oZoneSet->headerInfo->type) ||
      !isset($oZoneSet->headerInfo->version)
    ) {
      throw new MissingRequiredEntityException("Missing or incomplete headerInfo structure");
    }

    $this->oLog->debug("Full headerInfo section exists, checking");
    // check field types
    if (
      $oZoneSet->headerInfo->type != self::S_TYPE ||
      !preg_match("/^\d+\.\d+$/", $oZoneSet->headerInfo->version)
    ) {
      throw new InvalidZoneDataException("headerInfo field data invalid");
    }
    
    // check actual version
    list ($iVersion, $iRevision) = sscanf($oZoneSet->headerInfo->version, "%d.%d");
    if ($iVersion > self::I_VERSION) {
      throw new UnsupportedZoneVersionException("headerInfo version $iVersion.$iRevision not supported");
    }
    $this->oLog->debug("Header OK, version " . $oZoneSet->headerInfo->version);
  }
}

