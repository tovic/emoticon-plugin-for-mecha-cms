<?php

// Load the configuration data
$emoticon_config = File::open(PLUGIN . DS . basename(__DIR__) . DS . 'states' . DS . 'config.txt')->unserialize();

// Add the emoticon stylesheet
Weapon::add('shell_after', function() {
    echo Asset::stylesheet('cabinet/plugins/' . basename(__DIR__) . '/shell/icons.css');
    if(Config::get('page_type') === 'manager') {
        echo O_BEGIN . '<style>.table-emoticon-defines .se {font-size:1.4em}</style>' . O_END;
    }
}, 11);

// Build the emoticon parser
function simple_emoticon_parser($content) {
    global $emoticon_config;
    foreach($emoticon_config['defines'] as $icon => $patterns) {
        if(trim($patterns) !== "") {
            $patterns = preg_replace('# +#', '|', preg_quote($patterns, '#'));
            $content = preg_replace('#(^|[\s\>])(' . $patterns . ')#', '$1<span class="se">&#x' . $icon . ';</span>', $content);
        }
    }
    return $content;
}

// Remove broken emoticons in recent comment summary
Filter::add('widget:recent.comment', function($content) {
    return preg_replace('#&\#x[a-f0-9]+;#', ' ', $content);
});

// Register the filters
if(isset($emoticon_config['scopes']) && is_array($emoticon_config['scopes'])) {
    foreach($emoticon_config['scopes'] as $scope) {
        Filter::add($scope, 'simple_emoticon_parser');
    }
}


/**
 * Plugin Updater
 * --------------
 */

Route::accept($config->manager->slug . '/plugin/' . basename(__DIR__) . '/update', function() use($config, $speak) {
    if( ! Guardian::happy()) {
        Shield::abort();
    }
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        unset($request['token']); // Remove token from request array
        File::serialize($request)->saveTo(PLUGIN . DS . basename(__DIR__) . DS . 'states' . DS . 'config.txt', 0600);
        Notify::success(Config::speak('notify_success_updated', $speak->plugin));
        Guardian::kick(dirname($config->url_current));
    }
});