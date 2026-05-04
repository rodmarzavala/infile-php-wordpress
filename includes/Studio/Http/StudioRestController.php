<?php

namespace InfilePhp\WordPress\Studio\Http;

use WP_REST_Controller;
use WP_REST_Server;
use InfilePhp\WordPress\Studio\Storage\StudioRepository;
use InfilePhp\Core\InfilePhp;

class StudioRestController extends WP_REST_Controller
{
    private $repository;

    public function __construct()
    {
        $this->namespace = 'infile-php/v1';
        $this->rest_base = 'studio';
        $this->repository = new StudioRepository();
    }

    public static function register()
    {
        $controller = new self();
        add_action('rest_api_init', array($controller, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/timeline', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_timeline'),
                'permission_callback' => array($this, 'permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/timeline/clear', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'clear_timeline'),
                'permission_callback' => array($this, 'permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/health', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_health'),
                'permission_callback' => array($this, 'permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/builder/preview', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'builder_preview'),
                'permission_callback' => array($this, 'permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/builder/validate', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'builder_validate'),
                'permission_callback' => array($this, 'permissions_check'),
            ),
        ));
    }

    public function permissions_check($request)
    {
        return current_user_can('manage_options');
    }

    public function get_timeline($request)
    {
        return rest_ensure_response(array(
            'data' => $this->repository->getTimeline(),
        ));
    }

    public function clear_timeline($request)
    {
        $this->repository->clear();
        return rest_ensure_response(array('status' => 'cleared'));
    }

    public function get_health($request)
    {
        $start = microtime(true);
        $status = 'offline';
        $message = 'Infile API is unreachable or credentials are invalid.';

        try {
            $config = InfilePhp::config();
            $client = InfilePhp::client();

            // Try to perform a lightweight call, e.g. looking up a known NIT
            $client->lookupNit('CF');
            $status = 'online';
            $message = 'Infile API is reachable and credentials are valid.';
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $latency = round((microtime(true) - $start) * 1000);

        try {
            $config = InfilePhp::config();
            $environment = array(
                'hasSignUser' => !empty($config->signUser),
                'hasSignKey'  => !empty($config->signKey),
                'hasApiUser'  => !empty($config->apiUser),
                'hasApiKey'   => !empty($config->apiKey),
                'hasNit'      => !empty($config->nit),
                'environment' => $config->environment->value,
                'flow'        => $config->flow->value,
            );
        } catch (\Exception $e) {
            $environment = array(
                'hasSignUser' => false,
                'hasSignKey'  => false,
                'hasApiUser'  => false,
                'hasApiKey'   => false,
                'hasNit'      => false,
                'environment' => null,
                'flow'        => null,
            );
        }

        return rest_ensure_response(array(
            'status' => $status,
            'message' => $message,
            'latency' => $latency . 'ms',
            'environment' => $environment,
        ));
    }

    public function builder_preview($request)
    {
        $params = $request->get_json_params();

        if (empty($params['recipient']) || empty($params['items'])) {
            return rest_ensure_response(array('error' => 'Missing recipient or items'));
        }

        try {
            $invoice = \InfilePhp\Core\Dte\Invoice::create();

            if (!empty($params['recipient']['tax_id']) && $params['recipient']['tax_id'] === 'CF') {
                $invoice->forFinalConsumer();
            } else {
                $recipient = \InfilePhp\Core\Dte\Recipient::withTaxId($params['recipient']['tax_id'])
                    ->name($params['recipient']['name'] ?? '')
                    ->address($params['recipient']['address'] ?? '');
                $invoice->for($recipient);
            }

            foreach ($params['items'] as $itemData) {
                $item = $itemData['type'] === 'S' 
                    ? \InfilePhp\Core\Dte\Item::service($itemData['description'])
                    : \InfilePhp\Core\Dte\Item::product($itemData['description']);

                $item->quantity((float) $itemData['quantity'])
                     ->unitPrice((float) $itemData['unit_price']);

                if (!empty($itemData['discounts'])) {
                    $item->discount((float) $itemData['discounts']);
                }

                $invoice->add($item);
            }

            $xml = $invoice->toXml();

            return rest_ensure_response(array(
                'success' => true,
                'xml' => $xml
            ));
        } catch (\Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => $e->getMessage()
            ));
        }
    }

    public function builder_validate($request)
    {
        $previewResponse = $this->builder_preview($request);
        $data = $previewResponse->get_data();

        if (empty($data['success'])) {
            return $previewResponse;
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Valid structure',
        ));
    }
}
