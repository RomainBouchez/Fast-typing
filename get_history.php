<?php
// Inclure le fichier de configuration
require_once "database.php";

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    // Renvoyer une réponse JSON indiquant que l'utilisateur n'est pas connecté
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Vous devez être connecté pour consulter votre historique."]);
    exit;
}

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

// Récupérer les 10 dernières parties de l'utilisateur
try {
    $sql = "SELECT game_id, wpm, precision_score, errors, language, date_played 
            FROM game_history 
            WHERE user_id = :user_id 
            ORDER BY date_played DESC 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $history = $stmt->fetchAll();
    
    // Formater les dates pour l'affichage
    foreach ($history as &$game) {
        $date = new DateTime($game['date_played']);
        $game['date_formatted'] = $date->format('d/m/Y H:i');
        
        // Traduire le nom de la langue
        $game['language_display'] = ($game['language'] == 'french') ? 'Français' : 'Anglais';
    }
    
    // Retourner l'historique en format JSON
    header('Content-Type: application/json');
    echo json_encode(["success" => true, "history" => $history]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Erreur lors de la récupération de l'historique: " . $e->getMessage()]);
}

exit;
?>