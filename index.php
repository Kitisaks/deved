<?php
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/libs.php";
require_once __DIR__ . "/config/secure.php";
require_once __DIR__ . "/config/plug.php";
require_once __DIR__ . "/config/repo.php";
require_once __DIR__ . "/config/api.php";
require_once __DIR__ . "/config/view.php";
require_once __DIR__ . "/config/router.php";

$app = new Router($_SERVER["REQUEST_URI"]);
