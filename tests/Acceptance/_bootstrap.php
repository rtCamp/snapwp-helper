<?php
/**
 * Acceptance suite bootstrap file.
 *
 * This file is loaded AFTER the suite modules are initialized and WordPress has been loaded by the WPLoader module.
 *
 * The initial state of the WordPress site is the one set up by the dump file(s) loaded by the WPDb module, look for the
 * "modules.config.WPDb.dump" setting in the suite configuration file. The database will be dropped after each test
 * and re-created from the dump file(s).
 */
