<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
if (!isset($siteConfig) || !is_array($siteConfig)) {
    $siteConfig = require $projectRoot . '/config/site.php';
}
require_once $projectRoot . '/includes/helpers.php';
require_once $projectRoot . '/config/db.php';
require_once $projectRoot . '/includes/repository.php';
require_once $projectRoot . '/includes/customer.php';

/* CMS overrides: konten dari MySQL menang atas nilai default di config. */
$siteConfig = lk_site_config($siteConfig);

/* Auth links disimpan relatif terhadap folder situs, supaya situs tetap jalan
   di subfolder (mis. /Laundry%20Kita/) maupun di root domain. */
foreach (['login_url', 'register_url'] as $authKey) {
    if (isset($siteConfig['auth'][$authKey]) && is_string($siteConfig['auth'][$authKey])) {
        $siteConfig['auth'][$authKey] = ltrim($siteConfig['auth'][$authKey], '/');
    }
}

/* ----------------------------------------------------------------------------
   3. Auth integration readiness. Set 'backend_ready' => true once /login.php
      and /register.php exist, the CTAs then become live links automatically.
   ------------------------------------------------------------------------- */
$authReady = $siteConfig['auth']['backend_ready'];
if ($authReady === null) {
    $loginFile    = $projectRoot . '/' . ltrim((string) parse_url($siteConfig['auth']['login_url'], PHP_URL_PATH), '/');
    $registerFile = $projectRoot . '/' . ltrim((string) parse_url($siteConfig['auth']['register_url'], PHP_URL_PATH), '/');
    $authReady    = is_file($loginFile) && is_file($registerFile);
}

require $projectRoot . '/includes/sequence.php';
