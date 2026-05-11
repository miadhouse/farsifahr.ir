<?php
// incloud/i18n.php

/**
 * Get current language from session or cookie
 * @return string
 */
function get_current_lang() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa', 'de', 'en'])) {
        $_SESSION['lang'] = $_GET['lang'];
        setcookie('site_lang', $_GET['lang'], time() + (86400 * 30), "/");
        return $_GET['lang'];
    }
    
    if (isset($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    
    if (isset($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], ['fa', 'de', 'en'])) {
        return $_COOKIE['site_lang'];
    }
    
    return 'fa'; // Default language
}

/**
 * Global translations cache
 */
$GLOBALS['site_translations'] = null;

/**
 * Load all translations for the current language
 * @param string $lang
 * @param PDO $pdo
 */
function load_translations($lang, $pdo) {
    if ($GLOBALS['site_translations'] !== null) return;
    
    $stmt = $pdo->prepare("SELECT trans_key, $lang as val FROM site_translations");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $GLOBALS['site_translations'] = $results;
}

/**
 * Translate a key
 * @param string $key
 * @param string $default
 * @return string
 */
function __($key, $default = null) {
    global $pdo;
    $lang = get_current_lang();
    
    if ($GLOBALS['site_translations'] === null) {
        load_translations($lang, $pdo);
    }
    
    if (isset($GLOBALS['site_translations'][$key])) {
        return $GLOBALS['site_translations'][$key];
    }
    
    return $default ?? $key;
}

/**
 * Get language direction
 * @return string
 */
function get_lang_dir() {
    return get_current_lang() === 'fa' ? 'rtl' : 'ltr';
}
