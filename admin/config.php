
<?php
/**
 * Admin/API DB bootstrap
 *
 * This file is included by several API endpoints that expect `$conn` (MySQLi).
 * Reuse the main project database config so every page/API shares one source.
 */
require_once __DIR__ . '/../config.php';