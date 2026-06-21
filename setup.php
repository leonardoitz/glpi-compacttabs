<?php

define('PLUGIN_COMPACTTABS_VERSION', '0.1.1');
define('PLUGIN_COMPACTTABS_MIN_GLPI', '10.0.0');
define('PLUGIN_COMPACTTABS_MAX_GLPI', '10.0.99');

function plugin_init_compacttabs()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['compacttabs'] = true;
    $PLUGIN_HOOKS['config_page']['compacttabs'] = 'front/config.form.php';

    if (!Plugin::isPluginActive('compacttabs')) {
        return;
    }

    $config = Config::getConfigurationValues('plugin:compacttabs');

    $enable_ticket  = (int)($config['enable_ticket'] ?? 1) === 1;
    $enable_problem = (int)($config['enable_problem'] ?? 0) === 1;
    $enable_change  = (int)($config['enable_change'] ?? 0) === 1;
    $enable_formcreator_issue = (int)($config['enable_formcreator_issue'] ?? 1) === 1;

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    $should_load_assets = false;

    if ($enable_ticket && strpos($request_uri, 'ticket.form.php') !== false) {
        $should_load_assets = true;
    }

    if ($enable_problem && strpos($request_uri, 'problem.form.php') !== false) {
        $should_load_assets = true;
    }

    if ($enable_change && strpos($request_uri, 'change.form.php') !== false) {
        $should_load_assets = true;
    }

    if (
        $enable_formcreator_issue
        && strpos($request_uri, '/plugins/formcreator/front/issue.form.php') !== false
    ) {
        $should_load_assets = true;
    }

    if ($should_load_assets) {
        $PLUGIN_HOOKS['add_css']['compacttabs'][] = 'css/compacttabs.css';
        $PLUGIN_HOOKS['add_javascript']['compacttabs'][] = 'front/compacttabs.config.js.php';
        $PLUGIN_HOOKS['add_javascript']['compacttabs'][] = 'js/compacttabs.js';
    }
}

function plugin_version_compacttabs()
{
    return [
        'name'           => 'Compact Tabs',
        'version'        => PLUGIN_COMPACTTABS_VERSION,
        'author'         => 'Hugo Leonardo',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/leonardoitz/glpi-compacttabs/',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_COMPACTTABS_MIN_GLPI,
                'max' => PLUGIN_COMPACTTABS_MAX_GLPI,
            ],
            'php' => [
                'min' => '8.0',
            ],
        ],
    ];
}

function plugin_compacttabs_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, PLUGIN_COMPACTTABS_MIN_GLPI, 'lt')) {
        echo 'Este plugin requer GLPI >= ' . PLUGIN_COMPACTTABS_MIN_GLPI;
        return false;
    }

    if (version_compare(GLPI_VERSION, PLUGIN_COMPACTTABS_MAX_GLPI, 'gt')) {
        echo 'Este plugin foi validado para GLPI 10.0.x';
        return false;
    }

    return true;
}

function plugin_compacttabs_check_config($verbose = false)
{
    return true;
}
