<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once __DIR__ . '/src/Monime/core/Env.php';

\Monime\core\Env::clear();
