<?php

include('../../../inc/includes.php');

require_once __DIR__ . '/../inc/history.function.php';

Session::checkRight('config', UPDATE);

function compacttabs_get_selected_tabs(array $config, string $key): array
{
    if (empty($config[$key])) {
        return [];
    }

    $decoded = json_decode($config[$key], true);

    if (!is_array($decoded)) {
        return [];
    }

    return array_values($decoded);
}

function compacttabs_filter_selected_tabs(array $posted_tabs, array $allowed_tabs): array
{
    if (empty($allowed_tabs)) {
        return [];
    }

    return array_values(array_intersect(
        $posted_tabs,
        array_keys($allowed_tabs)
    ));
}

function compacttabs_get_discovered_tab_options(array $config, string $key): array
{
    if (empty($config[$key])) {
        return [];
    }

    $decoded = json_decode($config[$key], true);

    if (!is_array($decoded)) {
        return [];
    }

    $options = [];

    foreach ($decoded as $tab_key => $tab_data) {
        if ($tab_key === '-1') {
            continue;
        }

        if (substr($tab_key, -5) === '$main') {
            continue;
        }

        $label = trim((string)($tab_data['label'] ?? ''));

        if ($label === '') {
            $label = $tab_key;
        }

        $options[$tab_key] = $label;
    }

    asort($options, SORT_NATURAL | SORT_FLAG_CASE);

    return $options;
}

function compacttabs_merge_tab_options(array $fixed_options, array $discovered_options): array
{
    $merged_options = $fixed_options + $discovered_options;

    asort($merged_options, SORT_NATURAL | SORT_FLAG_CASE);

    return $merged_options;
}

function compacttabs_render_switch(
    string $id,
    string $name,
    string $label,
    bool $checked,
    string $help = ''
): void {
?>
    <div class="form-check form-switch mb-3">
        <input
            type="checkbox"
            class="form-check-input"
            id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
            name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
            value="1"
            <?php echo $checked ? 'checked' : ''; ?>>
        <label class="form-check-label fw-bold" for="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
        </label>

        <?php if ($help !== ''): ?>
            <div class="form-text">
                <?php echo htmlspecialchars($help, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}

function compacttabs_render_fixed_visible_items(): void
{
?>
    <div class="alert alert-info mb-3">
        Estas abas já ficam visíveis automaticamente: aba principal, aba ativa, aba Todos e abas com contador maior que zero.
    </div>
    <?php
}

function compacttabs_render_tab_options(
    string $input_name,
    array $options,
    array $selected_tabs
): void {
    if (empty($options)) {
    ?>
        <div class="alert alert-secondary mb-0">
            Nenhuma aba foi detectada ainda para esta tela. Acesse um registro desta tela com um usuário Super-Admin ou com permissão de configuração para que o plugin descubra as abas automaticamente.
        </div>
    <?php

        return;
    }

    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;">
                        <input
                            type="checkbox"
                            class="form-check-input compacttabs-check-all"
                            title="Selecionar ou desmarcar todas as abas">
                    </th>
                    <th>Aba</th>
                    <th>TabKey</th>
                    <th style="width: 160px;">Origem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($options as $tab_key => $label): ?>
                    <?php
                    $checked = in_array($tab_key, $selected_tabs, true);
                    $input_id = md5($input_name . '_' . $tab_key);
                    ?>

                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="form-check-input compacttabs-tab-checkbox"
                                id="<?php echo $input_id; ?>"
                                name="<?php echo htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8'); ?>[]"
                                value="<?php echo htmlspecialchars($tab_key, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $checked ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <label for="<?php echo $input_id; ?>" class="fw-bold">
                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($tab_key, ENT_QUOTES, 'UTF-8'); ?></code>
                        </td>
                        <td>
                            <?php if (strpos($tab_key, 'Plugin') === 0): ?>
                                <span class="badge bg-secondary">plugin</span>
                            <?php else: ?>
                                <span class="badge bg-info">nativa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}

function compacttabs_render_screen_card(
    string $title,
    string $description,
    string $input_name,
    array $options,
    array $selected_tabs
): void {
?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title mb-0">
                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
            </h2>
        </div>

        <div class="card-body">
            <p class="text-muted">
                <?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php compacttabs_render_fixed_visible_items(); ?>

            <?php compacttabs_render_tab_options($input_name, $options, $selected_tabs); ?>
        </div>
    </div>
<?php
}

function compacttabs_render_nav_button(
    string $id,
    string $target,
    string $label,
    bool $active = false,
    ?int $count = null,
    bool $disabled = false
): void {
?>
    <button
        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center compacttabs-config-tab <?php echo $active ? 'active' : ''; ?>"
        id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
        data-compacttabs-target="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>"
        type="button"
        <?php echo $disabled ? 'disabled' : ''; ?>>
        <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>

        <?php if ($count !== null): ?>
            <span class="badge bg-secondary">
                <?php echo (int)$count; ?>
            </span>
        <?php endif; ?>
    </button>
<?php
}

function compacttabs_canonical_history_value($value): string
{
    if (is_array($value)) {
        sort($value);
        return json_encode(array_values($value));
    }

    $decoded = json_decode((string)$value, true);

    if (is_array($decoded)) {
        sort($decoded);
        return json_encode(array_values($decoded));
    }

    return (string)$value;
}

function compacttabs_history_format_value(
    string $field,
    $value,
    array $options_by_field
): string {
    if (strpos($field, 'enable_') === 0) {
        return (int)$value === 1 ? 'Ativado' : 'Desativado';
    }

    $decoded = json_decode((string)$value, true);

    if (!is_array($decoded) || empty($decoded)) {
        return 'Nenhum';
    }

    $labels = [];

    foreach ($decoded as $tab_key) {
        $label = $options_by_field[$field][$tab_key] ?? $tab_key;
        $labels[] = $label . ' (' . $tab_key . ')';
    }

    sort($labels, SORT_NATURAL | SORT_FLAG_CASE);

    return implode("\n", $labels);
}

function compacttabs_json_array_value($value): array
{
    if (is_array($value)) {
        $items = $value;
    } else {
        $decoded = json_decode((string)$value, true);
        $items = is_array($decoded) ? $decoded : [];
    }

    $items = array_values(array_unique(array_map('strval', $items)));
    sort($items, SORT_NATURAL | SORT_FLAG_CASE);

    return $items;
}

function compacttabs_history_context_label(string $context): string
{
    $labels = [
        'general'           => 'Geral',
        'ticket'            => 'Chamados',
        'problem'           => 'Problemas',
        'change'            => 'Mudanças',
        'formcreator_issue' => 'FormCreator',
        'legacy'            => 'Legado',
    ];

    return $labels[$context] ?? $context;
}

function compacttabs_history_general_field_label(string $field): string
{
    $labels = [
        'enable_ticket'                     => 'Chamados',
        'enable_problem'                    => 'Problemas',
        'enable_change'                     => 'Mudanças',
        'enable_formcreator_issue'          => 'Solicitações do FormCreator',
        'enable_discovery'                  => 'Descoberta automática de abas',
        'enable_formcreator_user_discovery' => 'Descoberta via usuários da interface simplificada do FormCreator',
    ];

    return $labels[$field] ?? $field;
}

function compacttabs_history_field_label(
    string $context,
    string $field,
    array $options_by_context = []
): string {
    if ($context === 'general' || $context === 'legacy') {
        return compacttabs_history_general_field_label($field);
    }

    if (isset($options_by_context[$context][$field])) {
        return $options_by_context[$context][$field];
    }

    return $field;
}

function compacttabs_history_status_label($value): string
{
    $value = trim((string)$value);

    if ($value === '1') {
        return 'Ativado (1)';
    }

    if ($value === '0') {
        return 'Desativado (0)';
    }

    if ($value === '') {
        return 'Nenhum';
    }

    return $value;
}

function compacttabs_record_config_history(
    array $old_config,
    array $new_values,
    array $defaults,
    array $options_by_context
): void {
    $general_fields = [
        'enable_ticket',
        'enable_problem',
        'enable_change',
        'enable_formcreator_issue',
        'enable_discovery',
        'enable_formcreator_user_discovery',
    ];

    foreach ($general_fields as $field) {
        $old_value = (int)($old_config[$field] ?? ($defaults[$field] ?? 0));
        $new_value = (int)($new_values[$field] ?? 0);

        if ($old_value === $new_value) {
            continue;
        }

        compacttabs_history_insert(
            'update',
            'general',
            $field,
            (string)$old_value,
            (string)$new_value
        );
    }

    $tab_fields = [
        'ticket_always_visible_tabs'            => 'ticket',
        'problem_always_visible_tabs'           => 'problem',
        'change_always_visible_tabs'            => 'change',
        'formcreator_issue_always_visible_tabs' => 'formcreator_issue',
    ];

    foreach ($tab_fields as $field => $context) {
        $old_tabs = compacttabs_json_array_value(
            $old_config[$field] ?? ($defaults[$field] ?? json_encode([]))
        );

        $new_tabs = compacttabs_json_array_value(
            $new_values[$field] ?? json_encode([])
        );

        $all_tabs = array_values(array_unique(array_merge(
            array_keys($options_by_context[$context] ?? []),
            $old_tabs,
            $new_tabs
        )));

        sort($all_tabs, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($all_tabs as $tab_key) {
            $old_enabled = in_array($tab_key, $old_tabs, true) ? 1 : 0;
            $new_enabled = in_array($tab_key, $new_tabs, true) ? 1 : 0;

            if ($old_enabled === $new_enabled) {
                continue;
            }

            compacttabs_history_insert(
                'update',
                $context,
                $tab_key,
                (string)$old_enabled,
                (string)$new_enabled
            );
        }
    }
}

function compacttabs_history_allowed_limits(): array
{
    return [
        5,
        10,
        15,
        20,
        30,
        40,
        50,
        100,
        150,
        200,
        250,
        500,
        750,
        990,
        1000,
        2000,
        3000,
        4000,
        5000,
        10000,
    ];
}

function compacttabs_history_get_limit(): int
{
    $allowed_limits = compacttabs_history_allowed_limits();
    $limit = (int)($_GET['history_limit'] ?? 15);

    if (!in_array($limit, $allowed_limits, true)) {
        return 15;
    }

    return $limit;
}

function compacttabs_history_get_page(int $total, int $limit): int
{
    $last_page = max(1, (int)ceil($total / $limit));
    $page = (int)($_GET['history_page'] ?? 1);

    if ($page < 1) {
        return 1;
    }

    if ($page > $last_page) {
        return $last_page;
    }

    return $page;
}

function compacttabs_history_url(int $page, int $limit): string
{
    return Plugin::getWebDir('compacttabs')
        . '/front/config.form.php?'
        . http_build_query([
            'compacttabs_tab' => 'history',
            'history_page'   => $page,
            'history_limit'  => $limit,
        ]);
}

function compacttabs_render_history_limit_select(int $limit): void
{
    $allowed_limits = compacttabs_history_allowed_limits();

?>
    <div class="d-flex align-items-center gap-2">
        <select
            class="form-select form-select-sm compacttabs-history-limit"
            style="width: 120px;">
            <?php foreach ($allowed_limits as $allowed_limit): ?>
                <option
                    value="<?php echo (int)$allowed_limit; ?>"
                    <?php echo $limit === $allowed_limit ? 'selected' : ''; ?>>
                    <?php echo (int)$allowed_limit; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <span>linhas / página</span>
    </div>
<?php
}

function compacttabs_render_history_pagination(
    int $page,
    int $limit,
    int $total,
    string $position = 'top'
): void {
    $last_page = max(1, (int)ceil($total / $limit));
    $start = $total === 0 ? 0 : (($page - 1) * $limit) + 1;
    $end = min($page * $limit, $total);

?>
    <div class="compacttabs-history-pagination compacttabs-history-pagination-<?php echo htmlspecialchars($position, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-2">
            <div>
                <?php compacttabs_render_history_limit_select($limit); ?>
            </div>

            <div class="text-muted">
                Exibindo <?php echo (int)$start; ?> a <?php echo (int)$end; ?> de <?php echo (int)$total; ?> linhas
            </div>

            <?php if ($position === 'top'): ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="Paginação do histórico">
                    <?php
                    $first_disabled = $page <= 1;
                    $last_disabled = $page >= $last_page;
                    ?>

                    <a
                        class="btn btn-outline-secondary <?php echo $first_disabled ? 'disabled' : ''; ?>"
                        href="<?php echo $first_disabled ? '#' : compacttabs_history_url(1, $limit); ?>">
                        Primeira
                    </a>

                    <a
                        class="btn btn-outline-secondary <?php echo $first_disabled ? 'disabled' : ''; ?>"
                        href="<?php echo $first_disabled ? '#' : compacttabs_history_url($page - 1, $limit); ?>">
                        Anterior
                    </a>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($last_page, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a
                            class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                            href="<?php echo compacttabs_history_url($i, $limit); ?>">
                            <?php echo (int)$i; ?>
                        </a>
                    <?php endfor; ?>

                    <a
                        class="btn btn-outline-secondary <?php echo $last_disabled ? 'disabled' : ''; ?>"
                        href="<?php echo $last_disabled ? '#' : compacttabs_history_url($page + 1, $limit); ?>">
                        Próxima
                    </a>

                    <a
                        class="btn btn-outline-secondary <?php echo $last_disabled ? 'disabled' : ''; ?>"
                        href="<?php echo $last_disabled ? '#' : compacttabs_history_url($last_page, $limit); ?>">
                        Última
                    </a>
                </div>
            <?php else: ?>
                <div class="text-muted">
                    Página <?php echo (int)$page; ?> de <?php echo (int)$last_page; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
}

function compacttabs_render_history_card(array $options_by_context): void
{
    $total = compacttabs_history_count();
    $limit = compacttabs_history_get_limit();
    $page = compacttabs_history_get_page($total, $limit);
    $offset = ($page - 1) * $limit;
    $rows = compacttabs_history_get_rows($limit, $offset);

?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title mb-0">
                Histórico
            </h2>
        </div>

        <div class="card-body p-0">
            <?php compacttabs_render_history_pagination($page, $limit, $total, 'top'); ?>

            <?php if (empty($rows)): ?>
                <div class="alert alert-secondary m-3">
                    Nenhuma alteração registrada ainda.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 compacttabs-history-table">
                        <thead>
                            <tr>
                                <th class="compacttabs-history-id">ID</th>
                                <th class="compacttabs-history-date">Data</th>
                                <th class="compacttabs-history-user">Usuário</th>
                                <th class="compacttabs-history-action">Ação</th>
                                <th class="compacttabs-history-context">Contexto</th>
                                <th class="compacttabs-history-field">Campo</th>
                                <th class="compacttabs-history-old">Valor anterior</th>
                                <th class="compacttabs-history-new">Novo valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $context = (string)($row['context'] ?? 'legacy');
                                $field = (string)$row['field'];
                                ?>

                                <tr>
                                    <td><?php echo (int)$row['id']; ?></td>

                                    <td><?php echo Html::convDateTime($row['date_creation']); ?></td>

                                    <td>
                                        <?php
                                        $users_id = (int)$row['users_id'];

                                        if ($users_id > 0) {
                                            echo htmlspecialchars(getUserName($users_id), ENT_QUOTES, 'UTF-8');
                                            echo ' (' . $users_id . ')';
                                        } else {
                                            echo 'Sistema';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars((string)$row['action'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(compacttabs_history_context_label($context), ENT_QUOTES, 'UTF-8'); ?>
                                        <br>
                                        <code><?php echo htmlspecialchars($context, ENT_QUOTES, 'UTF-8'); ?></code>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(compacttabs_history_field_label($context, $field, $options_by_context), ENT_QUOTES, 'UTF-8'); ?>
                                        <br>
                                        <code><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?></code>
                                    </td>

                                    <td>
                                        <span class="compacttabs-history-value">
                                            <?php echo htmlspecialchars(compacttabs_history_status_label($row['old_value']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="compacttabs-history-value">
                                            <?php echo htmlspecialchars(compacttabs_history_status_label($row['new_value']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php compacttabs_render_history_pagination($page, $limit, $total, 'bottom'); ?>
        </div>
    </div>
<?php
}

$ticket_tab_options = [
    'PluginTimelineticketDisplay$1'          => 'Linha do Tempo',
    'PluginNextoolRuleinspectorExecution$1' => 'Rules Inspector',
    'PluginPdfTicket$1'                     => 'Imprimir em PDF',
    'PluginBehaviorsCommon$1'               => 'Clonar (Comportamentos)',
];

$problem_tab_options = [];
$change_tab_options = [];
$formcreator_issue_tab_options = [];

$config = Config::getConfigurationValues('plugin:compacttabs');

$ticket_tab_options = compacttabs_merge_tab_options(
    $ticket_tab_options,
    compacttabs_get_discovered_tab_options($config, 'ticket_discovered_tabs')
);

$problem_tab_options = compacttabs_merge_tab_options(
    $problem_tab_options,
    compacttabs_get_discovered_tab_options($config, 'problem_discovered_tabs')
);

$change_tab_options = compacttabs_merge_tab_options(
    $change_tab_options,
    compacttabs_get_discovered_tab_options($config, 'change_discovered_tabs')
);

$formcreator_issue_tab_options = compacttabs_merge_tab_options(
    $formcreator_issue_tab_options,
    compacttabs_get_discovered_tab_options($config, 'formcreator_issue_discovered_tabs')
);


$options_by_context = [
    'ticket'            => $ticket_tab_options,
    'problem'           => $problem_tab_options,
    'change'            => $change_tab_options,
    'formcreator_issue' => $formcreator_issue_tab_options,
];

if (isset($_POST['update'])) {
    $ticket_posted_tabs            = $_POST['ticket_always_visible_tabs'] ?? [];
    $problem_posted_tabs           = $_POST['problem_always_visible_tabs'] ?? [];
    $change_posted_tabs            = $_POST['change_always_visible_tabs'] ?? [];
    $formcreator_issue_posted_tabs = $_POST['formcreator_issue_always_visible_tabs'] ?? [];

    if (!is_array($ticket_posted_tabs)) {
        $ticket_posted_tabs = [];
    }

    if (!is_array($problem_posted_tabs)) {
        $problem_posted_tabs = [];
    }

    if (!is_array($change_posted_tabs)) {
        $change_posted_tabs = [];
    }

    if (!is_array($formcreator_issue_posted_tabs)) {
        $formcreator_issue_posted_tabs = [];
    }

    $ticket_always_visible_tabs = compacttabs_filter_selected_tabs(
        $ticket_posted_tabs,
        $ticket_tab_options
    );

    $problem_always_visible_tabs = compacttabs_filter_selected_tabs(
        $problem_posted_tabs,
        $problem_tab_options
    );

    $change_always_visible_tabs = compacttabs_filter_selected_tabs(
        $change_posted_tabs,
        $change_tab_options
    );

    $formcreator_issue_always_visible_tabs = compacttabs_filter_selected_tabs(
        $formcreator_issue_posted_tabs,
        $formcreator_issue_tab_options
    );

    $new_config_values = [
        'enable_ticket'                         => isset($_POST['enable_ticket']) ? 1 : 0,
        'enable_problem'                        => isset($_POST['enable_problem']) ? 1 : 0,
        'enable_change'                         => isset($_POST['enable_change']) ? 1 : 0,
        'enable_formcreator_issue'              => isset($_POST['enable_formcreator_issue']) ? 1 : 0,
        'enable_discovery'                      => isset($_POST['enable_discovery']) ? 1 : 0,
        'enable_formcreator_user_discovery'     => isset($_POST['enable_formcreator_user_discovery']) ? 1 : 0,
        'ticket_always_visible_tabs'            => json_encode($ticket_always_visible_tabs),
        'problem_always_visible_tabs'           => json_encode($problem_always_visible_tabs),
        'change_always_visible_tabs'            => json_encode($change_always_visible_tabs),
        'formcreator_issue_always_visible_tabs' => json_encode($formcreator_issue_always_visible_tabs),
    ];

    $config_defaults = [
        'enable_ticket'                         => 1,
        'enable_problem'                        => 0,
        'enable_change'                         => 0,
        'enable_formcreator_issue'              => 1,
        'enable_discovery'                      => 1,
        'enable_formcreator_user_discovery'     => 0,
        'ticket_always_visible_tabs'            => json_encode([]),
        'problem_always_visible_tabs'           => json_encode([]),
        'change_always_visible_tabs'            => json_encode([]),
        'formcreator_issue_always_visible_tabs' => json_encode([]),
    ];


    Config::setConfigurationValues('plugin:compacttabs', $new_config_values);

    compacttabs_record_config_history(
        $config,
        $new_config_values,
        $config_defaults,
        $options_by_context
    );

    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Configuração atualizada pelo usuário ID %s. Ticket: %s | Problem: %s | Change: %s | FormCreator: %s\n",
            date('Y-m-d H:i:s'),
            Session::getLoginUserID(),
            implode(', ', $ticket_always_visible_tabs),
            implode(', ', $problem_always_visible_tabs),
            implode(', ', $change_always_visible_tabs),
            implode(', ', $formcreator_issue_always_visible_tabs)
        ),
        true
    );

    Session::addMessageAfterRedirect('Configuração salva com sucesso.', true, INFO);

    Html::redirect(Plugin::getWebDir('compacttabs') . '/front/config.form.php');
}

$enable_ticket                    = (int)($config['enable_ticket'] ?? 1) === 1;
$enable_problem                   = (int)($config['enable_problem'] ?? 0) === 1;
$enable_change                    = (int)($config['enable_change'] ?? 0) === 1;
$enable_formcreator_issue         = (int)($config['enable_formcreator_issue'] ?? 1) === 1;
$enable_discovery                 = (int)($config['enable_discovery'] ?? 1) === 1;
$enable_formcreator_user_discovery = (int)($config['enable_formcreator_user_discovery'] ?? 0) === 1;

$ticket_always_visible_tabs = compacttabs_get_selected_tabs(
    $config,
    'ticket_always_visible_tabs'
);

$problem_always_visible_tabs = compacttabs_get_selected_tabs(
    $config,
    'problem_always_visible_tabs'
);

$change_always_visible_tabs = compacttabs_get_selected_tabs(
    $config,
    'change_always_visible_tabs'
);

$formcreator_issue_always_visible_tabs = compacttabs_get_selected_tabs(
    $config,
    'formcreator_issue_always_visible_tabs'
);

Html::header(
    'Compact Tabs',
    $_SERVER['PHP_SELF'],
    'config',
    'plugins'
);
?>

<div class="container-xl">
    <form method="post" action="<?php echo Plugin::getWebDir('compacttabs'); ?>/front/config.form.php">
        <div class="row">
            <div class="col-md-3 col-xl-2 mb-3">
                <div class="list-group" role="tablist">
                    <?php
                    compacttabs_render_nav_button(
                        'compacttabs-general-tab',
                        '#compacttabs-general',
                        'Geral',
                        true
                    );

                    compacttabs_render_nav_button(
                        'compacttabs-ticket-tab',
                        '#compacttabs-ticket',
                        'Chamado',
                        false,
                        count($ticket_tab_options)
                    );

                    compacttabs_render_nav_button(
                        'compacttabs-problem-tab',
                        '#compacttabs-problem',
                        'Problema',
                        false,
                        count($problem_tab_options)
                    );

                    compacttabs_render_nav_button(
                        'compacttabs-change-tab',
                        '#compacttabs-change',
                        'Mudança',
                        false,
                        count($change_tab_options)
                    );

                    compacttabs_render_nav_button(
                        'compacttabs-formcreator-tab',
                        '#compacttabs-formcreator',
                        'FormCreator',
                        false,
                        count($formcreator_issue_tab_options)
                    );

                    compacttabs_render_nav_button(
                        'compacttabs-history-tab',
                        '#compacttabs-history',
                        'Histórico',
                        false,
                        compacttabs_history_count()
                    );
                    ?>
                </div>
            </div>

            <div class="col-md-9 col-xl-10">
                <div class="tab-content">
                    <div
                        class="tab-pane fade show active compacttabs-config-pane"
                        id="compacttabs-general"
                        role="tabpanel"
                        aria-labelledby="compacttabs-general-tab">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title mb-0">
                                    Compact Tabs
                                </h2>
                            </div>

                            <div class="card-body">
                                <p class="text-muted">
                                    O Compact Tabs reduz a poluição visual das telas do GLPI, ocultando abas vazias e mantendo visíveis apenas as abas úteis para navegação e operação.
                                </p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <?php
                                        compacttabs_render_switch(
                                            'enable_ticket',
                                            'enable_ticket',
                                            'Chamados',
                                            $enable_ticket,
                                            'Aplica a compactação nas abas da tela de chamados.'
                                        );

                                        compacttabs_render_switch(
                                            'enable_problem',
                                            'enable_problem',
                                            'Problemas',
                                            $enable_problem,
                                            'Aplica a compactação nas abas da tela de problemas.'
                                        );

                                        compacttabs_render_switch(
                                            'enable_change',
                                            'enable_change',
                                            'Mudanças',
                                            $enable_change,
                                            'Aplica a compactação nas abas da tela de mudanças.'
                                        );

                                        compacttabs_render_switch(
                                            'enable_formcreator_issue',
                                            'enable_formcreator_issue',
                                            'Solicitações do FormCreator',
                                            $enable_formcreator_issue,
                                            'Aplica a compactação nas abas da tela simplificada de solicitações do FormCreator.'
                                        );
                                        ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php
                                        compacttabs_render_switch(
                                            'enable_discovery',
                                            'enable_discovery',
                                            'Descoberta automática de abas',
                                            $enable_discovery,
                                            'Quando ativado, usuários com permissão de configuração ajudam o plugin a detectar automaticamente abas nas telas atendidas.'
                                        );

                                        compacttabs_render_switch(
                                            'enable_formcreator_user_discovery',
                                            'enable_formcreator_user_discovery',
                                            'Permitir descoberta via usuários da interface simplificada do FormCreator',
                                            $enable_formcreator_user_discovery,
                                            'Use temporariamente quando o Super-Admin for redirecionado para a tela nativa do chamado e não conseguir acessar diretamente a solicitação simplificada do FormCreator.'
                                        );
                                        ?>

                                        <div class="alert alert-warning mb-0">
                                            A descoberta automática não altera permissões, não remove abas e não modifica o core do GLPI. Ela apenas registra os tabKeys das abas encontradas.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="tab-pane fade compacttabs-config-pane"
                        id="compacttabs-ticket"
                        role="tabpanel"
                        aria-labelledby="compacttabs-ticket-tab">
                        <?php
                        compacttabs_render_screen_card(
                            'Abas sempre visíveis em Chamados',
                            'Selecione abas nativas ou de plugins que devem continuar visíveis mesmo quando não tiverem contador.',
                            'ticket_always_visible_tabs',
                            $ticket_tab_options,
                            $ticket_always_visible_tabs
                        );
                        ?>
                    </div>

                    <div
                        class="tab-pane fade compacttabs-config-pane"
                        id="compacttabs-problem"
                        role="tabpanel"
                        aria-labelledby="compacttabs-problem-tab">
                        <?php
                        compacttabs_render_screen_card(
                            'Abas sempre visíveis em Problemas',
                            'Selecione abas nativas ou de plugins que devem continuar visíveis mesmo quando não tiverem contador.',
                            'problem_always_visible_tabs',
                            $problem_tab_options,
                            $problem_always_visible_tabs
                        );
                        ?>
                    </div>

                    <div
                        class="tab-pane fade compacttabs-config-pane"
                        id="compacttabs-change"
                        role="tabpanel"
                        aria-labelledby="compacttabs-change-tab">
                        <?php
                        compacttabs_render_screen_card(
                            'Abas sempre visíveis em Mudanças',
                            'Selecione abas nativas ou de plugins que devem continuar visíveis mesmo quando não tiverem contador.',
                            'change_always_visible_tabs',
                            $change_tab_options,
                            $change_always_visible_tabs
                        );
                        ?>
                    </div>

                    <div
                        class="tab-pane fade compacttabs-config-pane"
                        id="compacttabs-formcreator"
                        role="tabpanel"
                        aria-labelledby="compacttabs-formcreator-tab">
                        <?php
                        compacttabs_render_screen_card(
                            'Abas sempre visíveis em Solicitações do FormCreator',
                            'Selecione abas nativas ou de plugins que devem continuar visíveis mesmo quando não tiverem contador.',
                            'formcreator_issue_always_visible_tabs',
                            $formcreator_issue_tab_options,
                            $formcreator_issue_always_visible_tabs
                        );
                        ?>
                    </div>

                    <div
                        class="tab-pane fade compacttabs-config-pane"
                        id="compacttabs-history"
                        role="tabpanel"
                        aria-labelledby="compacttabs-history-tab">
                        <?php compacttabs_render_history_card($options_by_context); ?>
                    </div>
                </div>

                <input
                    type="hidden"
                    name="_glpi_csrf_token"
                    value="<?php echo Session::getNewCSRFToken(); ?>">

                <div
                    class="d-flex justify-content-end mt-3 mb-4"
                    id="compacttabs-save-wrapper">
                    <button type="submit" name="update" value="1" class="btn btn-primary">
                        Salvar configuração
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .compacttabs-history-table {
        min-width: 1450px;
        table-layout: fixed;
    }

    .compacttabs-history-id {
        width: 70px;
    }

    .compacttabs-history-date {
        width: 150px;
    }

    .compacttabs-history-user {
        width: 190px;
    }

    .compacttabs-history-action {
        width: 100px;
    }

    .compacttabs-history-context {
        width: 180px;
    }

    .compacttabs-history-field {
        width: 280px;
    }

    .compacttabs-history-old,
    .compacttabs-history-new {
        width: 230px;
    }

    .compacttabs-history-value {
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        line-height: 1.35;
    }

    .compacttabs-history-table td,
    .compacttabs-history-table th {
        vertical-align: top;
    }

    .compacttabs-history-pagination {
        background: var(--tblr-bg-surface-secondary, #f8f9fa);
        border-bottom: 1px solid rgba(0, 0, 0, .08);
    }

    .compacttabs-history-pagination-bottom {
        border-top: 1px solid rgba(0, 0, 0, .08);
        border-bottom: 0;
    }

    .compacttabs-history-pagination .btn.disabled {
        pointer-events: none;
        opacity: .45;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAllBoxes = Array.from(document.querySelectorAll('.compacttabs-check-all'));
        const configTabs = Array.from(document.querySelectorAll('.compacttabs-config-tab'));
        const configPanes = Array.from(
            document.querySelectorAll('.tab-content > .compacttabs-config-pane')
        );
        const saveWrapper = document.getElementById('compacttabs-save-wrapper');

        function getTableCheckboxes(checkAllBox) {
            const table = checkAllBox.closest('table');

            if (!table) {
                return [];
            }

            return Array.from(table.querySelectorAll('.compacttabs-tab-checkbox'));
        }

        function getTableCheckAllBox(tabCheckbox) {
            const table = tabCheckbox.closest('table');

            if (!table) {
                return null;
            }

            return table.querySelector('.compacttabs-check-all');
        }

        function updateCheckAllState(checkAllBox) {
            const tabCheckboxes = getTableCheckboxes(checkAllBox);
            const total = tabCheckboxes.length;
            const checked = tabCheckboxes.filter(function(checkbox) {
                return checkbox.checked;
            }).length;

            checkAllBox.checked = total > 0 && checked === total;
            checkAllBox.indeterminate = checked > 0 && checked < total;
        }

        function updateAllCheckAllStates() {
            checkAllBoxes.forEach(function(checkAllBox) {
                updateCheckAllState(checkAllBox);
            });
        }

        checkAllBoxes.forEach(function(checkAllBox) {
            checkAllBox.addEventListener('change', function() {
                const tabCheckboxes = getTableCheckboxes(checkAllBox);

                tabCheckboxes.forEach(function(item) {
                    item.checked = checkAllBox.checked;
                });

                updateCheckAllState(checkAllBox);
            });
        });

        document.querySelectorAll('.compacttabs-tab-checkbox').forEach(function(tabCheckbox) {
            tabCheckbox.addEventListener('change', function() {
                const checkAllBox = getTableCheckAllBox(tabCheckbox);

                if (checkAllBox) {
                    updateCheckAllState(checkAllBox);
                }
            });
        });

        function activateConfigTab(targetSelector) {
            configTabs.forEach(function(button) {
                button.classList.remove('active');
            });

            configPanes.forEach(function(pane) {
                pane.classList.remove('show');
                pane.classList.remove('active');
            });

            const activeButton = document.querySelector(
                '.compacttabs-config-tab[data-compacttabs-target="' + targetSelector + '"]'
            );

            const activePane = document.querySelector(targetSelector);

            if (activeButton) {
                activeButton.classList.add('active');
            }

            if (activePane) {
                activePane.classList.add('show');
                activePane.classList.add('active');
            }

            if (saveWrapper) {
                if (targetSelector === '#compacttabs-history') {
                    saveWrapper.classList.add('d-none');
                } else {
                    saveWrapper.classList.remove('d-none');
                }
            }

            updateAllCheckAllStates();
        }

        configTabs.forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();

                if (button.disabled) {
                    return;
                }

                const target = button.getAttribute('data-compacttabs-target');

                if (target) {
                    activateConfigTab(target);
                }
            });
        });

        const urlParams = new URLSearchParams(window.location.search);

        const tabMap = {
            general: '#compacttabs-general',
            ticket: '#compacttabs-ticket',
            problem: '#compacttabs-problem',
            change: '#compacttabs-change',
            formcreator: '#compacttabs-formcreator',
            history: '#compacttabs-history'
        };

        const requestedTab = urlParams.get('compacttabs_tab');

        const initialActive = document.querySelector('.compacttabs-config-tab.active');
        const initialTarget = tabMap[requestedTab] ||
            (
                initialActive ?
                initialActive.getAttribute('data-compacttabs-target') :
                '#compacttabs-general'
            );

        document.querySelectorAll('.compacttabs-history-limit').forEach(function(select) {
            select.addEventListener('change', function() {
                const nextLimit = select.value;

                const nextUrl = new URL(window.location.href);

                nextUrl.searchParams.set('compacttabs_tab', 'history');
                nextUrl.searchParams.set('history_page', '1');
                nextUrl.searchParams.set('history_limit', nextLimit);

                window.location.href = nextUrl.toString();
            });
        });

        updateAllCheckAllStates();
        activateConfigTab(initialTarget || '#compacttabs-general');
    });
</script>

<?php
Html::footer();
