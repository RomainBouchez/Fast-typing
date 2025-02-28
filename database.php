<?php
// Paramètres de connexion à la base de données
$db_host = 'localhost';  // Généralement localhost pour WAMP
$db_name = 'typing_game';
$db_user = 'root';       // Utilisateur par défaut pour WAMP
$db_pass = '';           // Mot de passe par défaut (vide) pour WAMP

// Création de la connexion PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    
    // Configuration des options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // En cas d'erreur de connexion
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour nettoyer les entrées utilisateur
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier si l'utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger vers une autre page
function redirect($url) {
    header("Location: $url");
    exit;
}

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}