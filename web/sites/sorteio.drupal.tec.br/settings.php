<?php

// phpcs:ignoreFile

/**
 * Production settings for sorteio.drupal.tec.br on the GCP VM.
 *
 * The project root is /srv/www/sorteio and the Drupal document root is
 * /srv/www/sorteio/web. Keep the Nginx root pointed at the latter.
 */

$project_root = dirname($app_root);

$databases['default']['default'] = [
  'database' => $project_root . '/database/sorteio.sqlite',
  'prefix' => '',
  'driver' => 'sqlite',
  'namespace' => 'Drupal\\sqlite\\Driver\\Database\\sqlite',
  'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
];

$settings['config_sync_directory'] = $project_root . '/config/global';
$settings['file_public_path'] = $site_path . '/files';
$settings['file_private_path'] = $project_root . '/private';
$settings['file_temp_path'] = '/tmp';
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$hash_salt_file = '/etc/sorteio/hash_salt';
$hash_salt = getenv('DRUPAL_HASH_SALT');
if ($hash_salt === FALSE && is_readable($hash_salt_file)) {
  $hash_salt = trim((string) file_get_contents($hash_salt_file));
}
if (!is_string($hash_salt) || $hash_salt === '') {
  throw new RuntimeException(
    'Configure DRUPAL_HASH_SALT or create /etc/sorteio/hash_salt before starting Drupal.'
  );
}
$settings['hash_salt'] = $hash_salt;

$settings['trusted_host_patterns'] = [
  '^sorteio\\.drupal\\.tec\\.br$',
];

// Set DRUPAL_DEPLOYMENT_IDENTIFIER to the deployed commit when available.
$settings['deployment_identifier'] = getenv('DRUPAL_DEPLOYMENT_IDENTIFIER') ?: 'gcp-production';
