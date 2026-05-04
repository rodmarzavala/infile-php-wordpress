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

        $signUser = (string) get_option('infile_sign_user', '');
        $signKey  = (string) get_option('infile_sign_key', '');
        $apiUser  = (string) get_option('infile_api_user', '');
        $apiKey   = (string) get_option('infile_api_key', '');
        $nit      = (string) get_option('infile_nit', '');
        $env      = (string) get_option('infile_environment', 'sandbox');
        $flow     = (string) get_option('infile_flow', 'unified');

        if (empty($nit) || empty($apiKey)) {
            return;
        }

        $envEnum = \InfilePhp\Core\Enums\Environment::tryFrom($env) ?? \InfilePhp\Core\Enums\Environment::Sandbox;
        $flowEnum = \InfilePhp\Core\Enums\Flow::tryFrom($flow) ?? \InfilePhp\Core\Enums\Flow::Unified;

        self::$config = new \InfilePhp\Core\FelConfig(
            $nit,
            $signUser,
            $signKey,
            $apiUser,
            $apiKey,
            $envEnum,
            $flowEnum
        );

        $httpClient = new \InfilePhp\WordPress\Http\WpRemoteHttpClient();
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $dispatcher = new \InfilePhp\WordPress\Core\WpEventDispatcher();

        \InfilePhp\Core\InfilePhp::configure(
            self::$config,
            $httpClient,
            $psr17Factory,
            $psr17Factory,
            $dispatcher
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
