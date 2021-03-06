<?php

class ZonePropertiesDefinitionValidator extends ZoneDataValidator implements ISingleZoneValidator {

  use TEnvDamageValidator;

  public function validate(stdClass $oZone) {
    if (isset($oZone->envHazards)) {
      $this->validateEnvHazards($oZone);
    }
  }

  private function validateEnvHazards(stdClass $oZone) {
    if (!is_array($oZone->envHazards)) {
      throw new ZoneValidationException('envHazards set must be an array');
    }
    foreach ($oZone->envHazards as $oDamage) {
      $this->validateEnvDamage($oDamage, 'Env Hazard ');
    }
  }
}

