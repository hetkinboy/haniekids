<?php

namespace App\Models;

use App\Libraries\AuthContext;
use CodeIgniter\Model;
use Throwable;

class StockBySizeModel extends Model
{
    protected $table = 'stock_by_size';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id',
        'size_option_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'avg_cost',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function insert($row = null, bool $returnID = true)
    {
        $result = parent::insert($row, $returnID);
        $id = $returnID ? $result : $this->getInsertID();

        if ($id !== false && $id !== null && $id !== 0 && $id !== '0') {
            $after = $this->find($id);
            $this->writeAuditLog('insert', (int) $id, null, $after, is_array($row) ? $row : null);
        } else {
            $this->writeAuditLog('insert_failed', null, null, null, is_array($row) ? $row : null);
        }

        return $result;
    }

    public function update($id = null, $row = null): bool
    {
        $ids = $this->normalizeIds($id);
        $beforeRows = [];

        foreach ($ids as $stockId) {
            $beforeRows[$stockId] = $this->find($stockId);
        }

        $result = parent::update($id, $row);

        if ($result) {
            if ($ids === []) {
                $this->writeAuditLog('update_unknown_scope', null, null, null, is_array($row) ? $row : null);
            }

            foreach ($ids as $stockId) {
                $after = $this->find($stockId);
                $this->writeAuditLog('update', (int) $stockId, $beforeRows[$stockId] ?? null, $after, is_array($row) ? $row : null);
            }
        } else {
            foreach ($ids as $stockId) {
                $this->writeAuditLog('update_failed', (int) $stockId, $beforeRows[$stockId] ?? null, null, is_array($row) ? $row : null);
            }
        }

        return $result;
    }

    public function findByProductAndSize(int $productId, int $sizeOptionId): ?array
    {
        return $this->where('product_id', $productId)
            ->where('size_option_id', $sizeOptionId)
            ->first();
    }

    private function normalizeIds(mixed $id): array
    {
        if (is_array($id)) {
            return array_values(array_filter(array_map(static fn (mixed $value): int => (int) $value, $id)));
        }

        if ($id === null || $id === '' || $id === 0 || $id === '0') {
            return [];
        }

        return [(int) $id];
    }

    private function writeAuditLog(string $action, ?int $stockId, ?array $before, ?array $after, ?array $payload): void
    {
        $record = [
            'logged_at' => date('Y-m-d H:i:s'),
            'action' => $action,
            'stock_id' => $stockId,
            'product_id' => $after['product_id'] ?? $before['product_id'] ?? $payload['product_id'] ?? null,
            'size_option_id' => $after['size_option_id'] ?? $before['size_option_id'] ?? $payload['size_option_id'] ?? null,
            'quantity_on_hand' => [
                'before' => $before === null ? null : (int) ($before['quantity_on_hand'] ?? 0),
                'after' => $after === null ? null : (int) ($after['quantity_on_hand'] ?? 0),
                'delta' => $before !== null && $after !== null
                    ? (int) ($after['quantity_on_hand'] ?? 0) - (int) ($before['quantity_on_hand'] ?? 0)
                    : null,
            ],
            'quantity_reserved' => [
                'before' => $before === null ? null : (int) ($before['quantity_reserved'] ?? 0),
                'after' => $after === null ? null : (int) ($after['quantity_reserved'] ?? 0),
                'delta' => $before !== null && $after !== null
                    ? (int) ($after['quantity_reserved'] ?? 0) - (int) ($before['quantity_reserved'] ?? 0)
                    : null,
            ],
            'quantity_available' => [
                'before' => $before === null ? null : (int) ($before['quantity_available'] ?? 0),
                'after' => $after === null ? null : (int) ($after['quantity_available'] ?? 0),
                'delta' => $before !== null && $after !== null
                    ? (int) ($after['quantity_available'] ?? 0) - (int) ($before['quantity_available'] ?? 0)
                    : null,
            ],
            'avg_cost' => [
                'before' => $before === null ? null : (float) ($before['avg_cost'] ?? 0),
                'after' => $after === null ? null : (float) ($after['avg_cost'] ?? 0),
            ],
            'payload' => $payload,
            'negative_stock' => $after !== null && (int) ($after['quantity_on_hand'] ?? 0) < 0,
            'user' => $this->auditUser(),
            'request' => $this->auditRequest(),
            'source' => $this->auditSource(),
        ];

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            return;
        }

        $logDir = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR;

        if (! is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $path = $logDir . 'stock-audit-' . date('Y-m-d') . '.log';
        @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function auditUser(): ?array
    {
        $user = AuthContext::user();

        if ($user === null) {
            return null;
        }

        return [
            'id' => (int) ($user['id'] ?? 0),
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'role' => $user['role'] ?? null,
        ];
    }

    private function auditRequest(): ?array
    {
        try {
            $request = service('request');

            return [
                'method' => method_exists($request, 'getMethod') ? strtoupper($request->getMethod()) : null,
                'uri' => method_exists($request, 'getUri') ? (string) $request->getUri() : null,
                'ip' => method_exists($request, 'getIPAddress') ? $request->getIPAddress() : null,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function auditSource(): ?array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);

        foreach ($trace as $frame) {
            $class = (string) ($frame['class'] ?? '');

            if ($class === self::class || str_starts_with($class, 'CodeIgniter\\')) {
                continue;
            }

            return [
                'class' => $class ?: null,
                'function' => $frame['function'] ?? null,
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
            ];
        }

        return null;
    }
}

