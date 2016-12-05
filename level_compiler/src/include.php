<?php

/**
 * Main include. We're not using an autoloader for a project this size.
 */
require_once 'utility/logging.php';
require_once 'error/io_exceptions.php';
require_once 'error/zone_exceptions.php';
require_once 'domain/export.php';
require_once 'domain/zone.php';
require_once 'domain/connection_matrix.php';
require_once 'process/zone_validate.php';
require_once 'process/zone_parse.php';
