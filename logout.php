<?php
// Inclure le fichier de configuration
require_once "database.php";

// Initialiser la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
redirect("login.php");
exit;
?>