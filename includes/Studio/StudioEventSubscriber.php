<?php

namespace InfilePhp\WordPress\Studio;

use InfilePhp\Core\Events\DteCancelled;
use InfilePhp\Core\Events\DteFailed;
use InfilePhp\Core\Events\DteIssued;
use InfilePhp\Core\Events\FallbackActivated;
use InfilePhp\WordPress\Studio\Storage\StudioRepository;

/**
 * Subscribes to WordPress actions that represent Core SDK events.
 * Logs the transactions into the StudioRepository.
 */
class StudioEventSubscriber
{
    /** @var StudioRepository */
    private $repository;

    public function __construct()
    {
        $this->repository = new StudioRepository();
    }

    public static function register()
    {
        $subscriber = new self();

        add_action(DteIssued::class, array($subscriber, 'onDteIssued'));
        add_action(DteFailed::class, array($subscriber, 'onDteFailed'));
        add_action(DteCancelled::class, array($subscriber, 'onDteCancelled'));
        add_action(FallbackActivated::class, array($subscriber, 'onFallbackActivated'));
    }

    public function onDteIssued($event)
    {
        if (!$event instanceof DteIssued) {
            return;
        }

        $this->repository->logTransaction(array(
            'uuid' => $event->uuid,
            'serie' => $event->serie,
            'numero' => $event->numero,
            'dte_type' => $event->dteType->value,
            'recipient_tax_id' => $event->recipientTaxId,
            'idempotency_key' => $event->idempotencyKey,
            'status' => 'issued',
            'payload' => array(
                'event' => 'DteIssued',
                'xml_certified' => $event->xmlCertified,
            ),
            'error_message' => null,
        ));
    }

    public function onDteFailed($event)
    {
        if (!$event instanceof DteFailed) {
            return;
        }

        $this->repository->logTransaction(array(
            'dte_type' => $event->dteType->value,
            'recipient_tax_id' => null,
            'idempotency_key' => $event->idempotencyKey,
            'status' => 'failed',
            'payload' => array(
                'event' => 'DteFailed',
                'exception_class' => $event->previous ? get_class($event->previous) : null,
            ),
            'error_message' => $event->errorMessage,
        ));
    }

    public function onDteCancelled($event)
    {
        if (!$event instanceof DteCancelled) {
            return;
        }

        $this->repository->logTransaction(array(
            'uuid' => $event->uuid,
            'status' => 'cancelled',
            'payload' => array(
                'event' => 'DteCancelled',
                'reason' => $event->reason,
            ),
            'error_message' => null,
        ));
    }

    public function onFallbackActivated($event)
    {
        if (!$event instanceof FallbackActivated) {
            return;
        }

        $this->repository->logTransaction(array(
            'dte_type' => null,
            'recipient_tax_id' => null,
            'idempotency_key' => $event->idempotencyKey,
            'status' => 'pending',
            'payload' => null,
            'error_message' => 'Contingencia CAFE activada. Se encoló para reintento.',
        ));
    }
}
