<?php

namespace InfilePhp\WordPress\Studio\Storage;

use PDO;
use RuntimeException;

/**
 * Handles storing and retrieving DTE transaction history for the Studio timeline.
 * Uses SQLite by default, falls back to JSON file.
 * Stored in wp-content/uploads/infile_studio to prevent loss on plugin updates.
 */
class StudioRepository
{
    private $dbPath;
    private $jsonFilePath;
    private $db;
    private $useSqlite = false;

    public function __construct()
    {
        $uploadDir = wp_upload_dir();
        $baseDir = $uploadDir['basedir'] . '/infile_studio';

        if (!file_exists($baseDir)) {
            wp_mkdir_p($baseDir);
            
            // Protect directory from web access
            file_put_contents($baseDir . '/.htaccess', "Order deny,allow\nDeny from all\n");
            file_put_contents($baseDir . '/index.php', "<?php\n// Silence is golden.\n");
        }

        $this->dbPath = $baseDir . '/fel-studio.sqlite';
        $this->jsonFilePath = $baseDir . '/fel-studio.json';

        if (in_array('sqlite', PDO::getAvailableDrivers())) {
            $this->useSqlite = true;
            $this->initializeSqlite();
        }
    }

    private function initializeSqlite()
    {
        try {
            $isNew = !file_exists($this->dbPath);
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($isNew) {
                $this->db->exec("
                    CREATE TABLE timeline (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        uuid TEXT,
                        serie TEXT,
                        numero TEXT,
                        dte_type TEXT,
                        recipient_tax_id TEXT,
                        idempotency_key TEXT,
                        status TEXT,
                        payload TEXT,
                        error_message TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            }
        } catch (\Exception $e) {
            $this->useSqlite = false;
            $this->db = null;
        }
    }

    public function logTransaction(array $data)
    {
        $payloadStr = isset($data['payload']) ? json_encode($data['payload']) : null;

        if ($this->useSqlite && $this->db) {
            $stmt = $this->db->prepare("
                INSERT INTO timeline (
                    uuid, serie, numero, dte_type, recipient_tax_id, 
                    idempotency_key, status, payload, error_message
                ) VALUES (
                    :uuid, :serie, :numero, :dte_type, :recipient_tax_id,
                    :idempotency_key, :status, :payload, :error_message
                )
            ");

            $stmt->execute([
                ':uuid' => $data['uuid'] ?? null,
                ':serie' => $data['serie'] ?? null,
                ':numero' => $data['numero'] ?? null,
                ':dte_type' => $data['dte_type'] ?? null,
                ':recipient_tax_id' => $data['recipient_tax_id'] ?? null,
                ':idempotency_key' => $data['idempotency_key'] ?? null,
                ':status' => $data['status'] ?? null,
                ':payload' => $payloadStr,
                ':error_message' => $data['error_message'] ?? null,
            ]);
            return;
        }

        $this->logToJson($data, $payloadStr);
    }

    public function getTimeline()
    {
        if ($this->useSqlite && $this->db) {
            $stmt = $this->db->query("SELECT * FROM timeline ORDER BY created_at DESC LIMIT 100");
            $results = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($results as &$row) {
                if (isset($row['payload'])) {
                    $row['payload'] = json_decode((string) $row['payload'], true);
                }
            }

            return $results;
        }

        $results = $this->getJsonTimeline();
        foreach ($results as &$row) {
            if (isset($row['payload']) && is_string($row['payload'])) {
                $row['payload'] = json_decode($row['payload'], true);
            }
        }
        return $results;
    }

    public function clear()
    {
        if ($this->useSqlite && $this->db) {
            $this->db->exec("DELETE FROM timeline");
            return;
        }

        if (file_exists($this->jsonFilePath)) {
            unlink($this->jsonFilePath);
        }
    }

    private function logToJson(array $data, $payloadStr)
    {
        $timeline = $this->getJsonTimeline();
        
        $data['id'] = count($timeline) + 1;
        $data['created_at'] = current_time('mysql');
        $data['payload'] = $payloadStr;
        
        array_unshift($timeline, $data);
        
        if (count($timeline) > 100) {
            $timeline = array_slice($timeline, 0, 100);
        }

        file_put_contents($this->jsonFilePath, json_encode($timeline));
    }

    private function getJsonTimeline()
    {
        if (!file_exists($this->jsonFilePath)) {
            return [];
        }
        $content = file_get_contents($this->jsonFilePath);
        return $content ? json_decode($content, true) : [];
    }
}
