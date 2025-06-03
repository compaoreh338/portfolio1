<?php
require_once 'config.php';

// Vérifier si la requête est de type GET et si le paramètre lang est présent
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    
    // Tenter de changer la langue
    if (setLanguage($lang)) {
        // Déterminer l'URL de redirection
        $redirect = 'index.php';
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
            $redirect = $_SERVER['HTTP_REFERER'];
        }
        
        // Rediriger vers la page appropriée
        header('Location: ' . $redirect);
        exit;
    }
}

// En cas d'échec, rediriger vers la page d'accueil
header('Location: index.php');
exit;
?> 