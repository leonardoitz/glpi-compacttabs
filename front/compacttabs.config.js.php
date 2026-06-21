<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

header('Content-Type: application/javascript; charset=UTF-8');

$config = Config::getConfigurationValues('plugin:compacttabs');

$can_configure = Session::haveRight('config', UPDATE);

$enable_discovery = (int)($config['enable_discovery'] ?? 1) === 1;

$enable_formcreator_user_discovery = (int)(
    $config['enable_formcreator_user_discovery'] ?? 0
) === 1;

$discovery_enabled = $enable_discovery
    && (
        $can_configure
        || $enable_formcreator_user_discovery
    );

function compacttabs_get_json_config_array(array $config, string $key): array
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

echo 'window.GLPICompactTabsConfig = ';
echo json_encode([
    'discovery' => [
        'enabled'   => $discovery_enabled,
        'url'       => Plugin::getWebDir('compacttabs') . '/front/discover.form.php',
        'csrfToken' => Session::getNewCSRFToken(),
    ],
    'ticket' => [
        'enabled'           => (int)($config['enable_ticket'] ?? 1) === 1,
        'itemtype'          => 'Ticket',
        'target'            => '/front/ticket.form.php',
        'mainTabKey'        => 'Ticket$main',
        'alwaysVisibleTabs' => compacttabs_get_json_config_array($config, 'ticket_always_visible_tabs'),
    ],
    'problem' => [
        'enabled'           => (int)($config['enable_problem'] ?? 0) === 1,
        'itemtype'          => 'Problem',
        'target'            => '/front/problem.form.php',
        'mainTabKey'        => 'Problem$main',
        'alwaysVisibleTabs' => compacttabs_get_json_config_array($config, 'problem_always_visible_tabs'),
    ],
    'change' => [
        'enabled'           => (int)($config['enable_change'] ?? 0) === 1,
        'itemtype'          => 'Change',
        'target'            => '/front/change.form.php',
        'mainTabKey'        => 'Change$main',
        'alwaysVisibleTabs' => compacttabs_get_json_config_array($config, 'change_always_visible_tabs'),
    ],
    'formcreator_issue' => [
        'enabled'           => (int)($config['enable_formcreator_issue'] ?? 1) === 1,
        'itemtype'          => '',
        'target'            => '/plugins/formcreator/front/issue.form.php',
        'mainTabKey'        => '',
        'alwaysVisibleTabs' => compacttabs_get_json_config_array($config, 'formcreator_issue_always_visible_tabs'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo ';';
