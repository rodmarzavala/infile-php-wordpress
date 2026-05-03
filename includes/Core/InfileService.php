<?php

namespace InfilePhp\WordPress\Core;

/**
 * Thin wrapper over the core SDK. Builds FelConfig from wp_options credentials.
 * PHP 7.4 compatible — no enums, no readonly, no named arguments.
 */
class InfileService
{
    /** @var \InfilePhp\Core\FelConfig|null */
    private static $config = null;

    /**
     * Initialize the core SDK using credentials stored in wp_options.
     */
    public static function init()
    {
        if (!class_exists('\InfilePhp\Core\FelConfig')) {
            return;
        }

        $signUser = get_option('infile_sign_user', '');
        $signKey  = get_option('infile_sign_key', '');
        $apiUser  = get_option('infile_api_user', '');
        $apiKey   = get_option('infile_api_key', '');
        $nit      = get_option('infile_nit', '');
        $env      = get_option('infile_environment', 'sandbox');
        $flow     = get_option('infile_flow', 'unified');

        if (empty($nit) || empty($apiKey)) {
            return;
        }

        self::$config = new \InfilePhp\Core\FelConfig(
            $nit,
            $signUser,
            $signKey,
            $apiUser,
            $apiKey,
            \InfilePhp\Core\Enums\Environment::from($env),
            \InfilePhp\Core\Enums\Flow::from($flow)
        );

        $httpClient = new \InfilePhp\WordPress\Http\WpRemoteHttpClient();
        $psr17Factory = new \InfilePhp\WordPress\Http\Psr7\WpPsr17Factory();

        \InfilePhp\Core\InfilePhp::configure(
            self::$config,
            $httpClient,
            $psr17Factory,
            $psr17Factory
        );
    }

    /**
     * Check whether the SDK has been configured with valid credentials.
     */
    public static function isConfigured()
    {
        return self::$config !== null;
    }

    /**
     * Return the active configuration, or null if not configured.
     *
     * @return \InfilePhp\Core\FelConfig|null
     */
    public static function getConfig()
    {
        return self::$config;
    }
}
