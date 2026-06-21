<?php

$compacttabs_buffer_level = ob_get_level();
ob_start();

include('../../../inc/includes.php');

function compacttabs_json_response(array $payload, int $status = 200, int $buffer_level = 0): void
{
    while (ob_get_level() > $buffer_level) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

function compacttabs_is_debug_request(): bool
{
    return isset($_REQUEST['compacttabs_debug'])
        && (string)$_REQUEST['compacttabs_debug'] === '1';
}

Session::checkLoginUser();

$debug = compacttabs_is_debug_request();

$config = Config::getConfigurationValues('plugin:compacttabs');

$enable_discovery = (int)($config['enable_discovery'] ?? 1) === 1;

$enable_formcreator_user_discovery = (int)(
    $config['enable_formcreator_user_discovery'] ?? 0
) === 1;

$allowed_screens = [
    'ticket'            => 'ticket_discovered_tabs',
    'problem'           => 'problem_discovered_tabs',
    'change'            => 'change_discovered_tabs',
    'formcreator_issue' => 'formcreator_issue_discovered_tabs',
];

$screen = trim((string)($_POST['screen'] ?? ''));

if (!isset($allowed_screens[$screen])) {
    compacttabs_json_response([
        'success' => false,
        'message' => 'Tela inválida.',
        'screen'  => $screen,
    ], 400, $compacttabs_buffer_level);
}

$can_configure = Session::haveRight('config', UPDATE);

$can_discover = $enable_discovery
    && (
        $can_configure
        || (
            $enable_formcreator_user_discovery
            && $screen === 'formcreator_issue'
        )
    );

if (!$can_discover) {
    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Descoberta bloqueada. Usuário ID %s | Tela: %s\n",
            date('Y-m-d H:i:s'),
            Session::getLoginUserID(),
            $screen
        ),
        true
    );

    compacttabs_json_response([
        'success' => false,
        'message' => 'Acesso negado para descoberta.',
        'screen'  => $screen,
    ], 403, $compacttabs_buffer_level);
}

$tabs_json = '';

$tabs_b64 = trim((string)($_POST['tabs_b64'] ?? ''));

if ($tabs_b64 !== '') {
    $decoded_b64 = base64_decode($tabs_b64, true);

    if ($decoded_b64 !== false) {
        $tabs_json = $decoded_b64;
    }
}

if ($tabs_json === '') {
    $tabs_json = trim((string)($_POST['tabs'] ?? '[]'));
}

$tabs = json_decode($tabs_json, true);

if (!is_array($tabs)) {
    $tabs_unslashed = stripslashes($tabs_json);
    $tabs = json_decode($tabs_unslashed, true);
}

if (!is_array($tabs)) {
    $tabs_decoded_entities = html_entity_decode(
        $tabs_json,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $tabs = json_decode($tabs_decoded_entities, true);
}

if (!is_array($tabs)) {
    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Payload inválido na descoberta. Tela: %s | Raw: %s\n",
            date('Y-m-d H:i:s'),
            $screen,
            $tabs_json
        ),
        true
    );

    compacttabs_json_response([
        'success' => false,
        'message' => 'Formato inválido.',
        'screen'  => $screen,
        'raw'     => $tabs_json,
    ], 400, $compacttabs_buffer_level);
}

$config_key = $allowed_screens[$screen];
$config = Config::getConfigurationValues('plugin:compacttabs');

$current_tabs = [];

if (!empty($config[$config_key])) {
    $decoded = json_decode($config[$config_key], true);

    if (is_array($decoded)) {
        $current_tabs = $decoded;
    }
}

$changed = false;
$received_plugin_tabs = [];
$new_tabs = [];
$updated_tabs = [];

foreach ($tabs as $tab) {
    $tab_key = trim((string)($tab['tabKey'] ?? ''));
    $label = trim((string)($tab['text'] ?? ''));

    if ($tab_key === '') {
        continue;
    }

    if ($tab_key === '-1') {
        continue;
    }

    if (substr($tab_key, -5) === '$main') {
        continue;
    }

    if ($label === '') {
        $label = $tab_key;
    }

    $origin = strpos($tab_key, 'Plugin') === 0 ? 'plugin' : 'native';

    $received_plugin_tabs[] = $tab_key;

    if (!isset($current_tabs[$tab_key])) {
        $current_tabs[$tab_key] = [
            'tabKey'     => $tab_key,
            'label'      => $label,
            'source'     => 'auto',
            'origin'     => $origin,
            'first_seen' => date('Y-m-d H:i:s'),
            'last_seen'  => date('Y-m-d H:i:s'),
        ];

        $new_tabs[] = $tab_key;
        $changed = true;

        continue;
    }

    $previous_label = $current_tabs[$tab_key]['label'] ?? '';

    if ($previous_label !== $label) {
        $current_tabs[$tab_key]['label'] = $label;
        $current_tabs[$tab_key]['origin'] = $origin;
        $current_tabs[$tab_key]['last_seen'] = date('Y-m-d H:i:s');

        if (empty($current_tabs[$tab_key]['first_seen'])) {
            $current_tabs[$tab_key]['first_seen'] = date('Y-m-d H:i:s');
        }

        $updated_tabs[] = $tab_key;
        $changed = true;
    }
}

if ($changed) {
    Config::setConfigurationValues('plugin:compacttabs', [
        $config_key => json_encode(
            $current_tabs,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ),
    ]);

    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Descoberta atualizada. Tela: %s | Novas: %s | Atualizadas: %s\n",
            date('Y-m-d H:i:s'),
            $screen,
            implode(', ', $new_tabs),
            implode(', ', $updated_tabs)
        ),
        true
    );
} elseif ($debug) {
    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Descoberta sem alteração. Tela: %s | Recebidas: %s\n",
            date('Y-m-d H:i:s'),
            $screen,
            implode(', ', $received_plugin_tabs)
        ),
        true
    );
}

compacttabs_json_response([
    'success' => true,
    'changed' => $changed,
    'screen'  => $screen,
    'received_plugin_tabs' => $received_plugin_tabs,
    'new_tabs' => $new_tabs,
    'updated_tabs' => $updated_tabs,
    'tabs' => $current_tabs,
], 200, $compacttabs_buffer_level);
