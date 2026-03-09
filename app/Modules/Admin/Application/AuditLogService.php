<?php

namespace App\Modules\Admin\Application;

use App\Core\DatabaseManager;

class AuditLogService
{
    public function log($adminUserId, $entityType, $entityId, $action, $before = null, $after = null)
    {
        $stmt = DatabaseManager::connection()->prepare(
            'INSERT INTO settings_audit_log (admin_user_id, entity_type, entity_id, action, before_json, after_json)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $adminUserId ?: null,
            $entityType,
            (string) $entityId,
            $action,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }
}
