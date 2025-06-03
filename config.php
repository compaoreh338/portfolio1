<?php
session_start();

// Configuration des langues disponibles
$available_languages = ['fr', 'en'];

// Définir la langue par défaut
$default_language = 'fr';

// Récupérer la langue actuelle depuis la session ou utiliser la langue par défaut
$current_language = isset($_SESSION['language']) ? $_SESSION['language'] : $default_language;

// Fonction pour changer la langue
function setLanguage($lang) {
    global $available_languages;
    if (in_array($lang, $available_languages)) {
        $_SESSION['language'] = $lang;
        return true;
    }
    return false;
}

// Fonction pour obtenir la langue actuelle
function getCurrentLanguage() {
    global $current_language;
    return $current_language;
}

// Fonction pour obtenir le texte traduit
function __($key) {
    global $translations, $current_language;
    return isset($translations[$current_language][$key]) ? $translations[$current_language][$key] : $key;
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
} 