<?php

require_once __DIR__ . '/inc/history.function.php';

function plugin_compacttabs_install()
{
    compacttabs_create_history_table();

    $config = Config::getConfigurationValues('plugin:compacttabs');

    $defaults = [
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
        'ticket_discovered_tabs'                => json_encode([]),
        'problem_discovered_tabs'               => json_encode([]),
        'change_discovered_tabs'                => json_encode([]),
        'formcreator_issue_discovered_tabs'     => json_encode([]),
    ];

    $missing = [];

    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $config)) {
            $missing[$key] = $value;
        }
    }

    if (!empty($missing)) {
        Config::setConfigurationValues('plugin:compacttabs', $missing);
    }

    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Plugin instalado ou atualizado para a versão %s\n",
            date('Y-m-d H:i:s'),
            PLUGIN_COMPACTTABS_VERSION
        ),
        true
    );

    return true;
}

function plugin_compacttabs_uninstall()
{
    global $DB;

    $config = new Config();

    $config->deleteByCriteria([
        'context' => 'plugin:compacttabs',
    ]);

    $table = compacttabs_history_table();

    if ($DB->tableExists($table)) {
        $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
    }

    Toolbox::logInFile(
        'compacttabs',
        sprintf(
            "[%s] Plugin desinstalado\n",
            date('Y-m-d H:i:s')
        ),
        true
    );

    return true;
}
