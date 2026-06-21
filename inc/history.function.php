<?php

function compacttabs_history_table(): string
{
    return 'glpi_plugin_compacttabs_histories';
}

function compacttabs_create_history_table(): bool
{
    global $DB;

    $table = compacttabs_history_table();

    if ($DB->tableExists($table)) {
        return true;
    }

    $query = "
        CREATE TABLE `$table` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `date_creation` timestamp NULL DEFAULT NULL,
            `users_id` int unsigned NOT NULL DEFAULT 0,
            `action` varchar(64) NOT NULL DEFAULT 'update',
            `context` varchar(64) NOT NULL DEFAULT 'general',
            `field` varchar(255) NOT NULL,
            `old_value` varchar(16) DEFAULT NULL,
            `new_value` varchar(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `date_creation` (`date_creation`),
            KEY `users_id` (`users_id`),
            KEY `context` (`context`),
            KEY `field` (`field`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $DB->queryOrDie($query, $DB->error());

    return true;
}

function compacttabs_history_insert(
    string $action,
    string $context,
    string $field,
    string $old_value,
    string $new_value,
    ?int $users_id = null
): bool {
    global $DB;

    $table = compacttabs_history_table();

    if (!$DB->tableExists($table)) {
        compacttabs_create_history_table();
    }

    if ($users_id === null) {
        $users_id = (int)Session::getLoginUserID();
    }

    return $DB->insert($table, [
        'date_creation' => date('Y-m-d H:i:s'),
        'users_id'     => $users_id,
        'action'       => $action,
        'context'      => $context,
        'field'        => $field,
        'old_value'    => $old_value,
        'new_value'    => $new_value,
    ]);
}

function compacttabs_history_count(): int
{
    global $DB;

    $table = compacttabs_history_table();

    if (!$DB->tableExists($table)) {
        return 0;
    }

    $result = $DB->query("SELECT COUNT(*) AS total FROM `$table`");

    if (!$result) {
        return 0;
    }

    if (method_exists($result, 'fetch_assoc')) {
        $row = $result->fetch_assoc();
    } else {
        $row = $DB->fetchAssoc($result);
    }

    return (int)($row['total'] ?? 0);
}

function compacttabs_history_get_rows(int $limit = 15, int $offset = 0): array
{
    global $DB;

    $table = compacttabs_history_table();

    if (!$DB->tableExists($table)) {
        return [];
    }

    $limit = max(1, min($limit, 10000));
    $offset = max(0, $offset);

    $rows = [];

    $result = $DB->query("
        SELECT
            `id`,
            `date_creation`,
            `users_id`,
            `action`,
            `context`,
            `field`,
            `old_value`,
            `new_value`
        FROM `$table`
        ORDER BY `id` DESC
        LIMIT $limit OFFSET $offset
    ");

    if (!$result) {
        return [];
    }

    while (true) {
        if (method_exists($result, 'fetch_assoc')) {
            $row = $result->fetch_assoc();
        } else {
            $row = $DB->fetchAssoc($result);
        }

        if (!$row) {
            break;
        }

        $rows[] = $row;
    }

    return $rows;
}
