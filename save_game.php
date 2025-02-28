<?php
// Inclure le fichier de configuration
require_once "database.php";

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Vous devez être connecté pour enregistrer vos résultats."]);
    exit;
}

// Vérifier si la méthode est POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
    exit;
}

// Récupérer et valider les données
$user_id = $_SESSION['user_id'];
$wpm = isset($_POST['wpm']) ? (int)$_POST['wpm'] : 0;
$precision = isset($_POST['precision']) ? (float)$_POST['precision'] : 0;
$errors = isset($_POST['errors']) ? (int)$_POST['errors'] : 0;
$language = isset($_POST['language']) ? $_POST['language'] : 'french';

// Validation des données
if ($wpm < 0 || $precision < 0 || $precision > 100 || $errors < 0) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Données invalides."]);
    exit;
}

// Journal de débogage
error_log("Saving game: User $user_id, WPM $wpm, Precision $precision, Errors $errors, Language $language");

try {
    // Début de la transaction
    $pdo->beginTransaction();
    
    // 1. Enregistrer la partie dans l'historique
    $sql = "INSERT INTO game_history (user_id, wpm, precision_score, errors, language) 
            VALUES (:user_id, :wpm, :precision, :errors, :language)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":wpm", $wpm, PDO::PARAM_INT);
    $stmt->bindParam(":precision", $precision, PDO::PARAM_STR);
    $stmt->bindParam(":errors", $errors, PDO::PARAM_INT);
    $stmt->bindParam(":language", $language, PDO::PARAM_STR);
    $stmt->execute();
    
    // 2. Vérifier et mettre à jour les records
    
    // WPM global
    $sql = "SELECT record_value FROM user_records 
            WHERE user_id = :user_id AND record_type = 'global_wpm'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_global_wpm = $stmt->fetchColumn();
    
    if ($wpm > $current_global_wpm) {
        $sql = "UPDATE user_records 
                SET record_value = :wpm, date_achieved = NOW() 
                WHERE user_id = :user_id AND record_type = 'global_wpm'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":wpm", $wpm, PDO::PARAM_INT);
        $stmt->execute();
        error_log("Updated global WPM record to $wpm");
    }
    
    // WPM par langue
    $wpm_record_type = ($language == 'french') ? 'french_wpm' : 'english_wpm';
    $sql = "SELECT record_value FROM user_records 
            WHERE user_id = :user_id AND record_type = :record_type";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":record_type", $wpm_record_type, PDO::PARAM_STR);
    $stmt->execute();
    $current_lang_wpm = $stmt->fetchColumn();
    
    if ($wpm > $current_lang_wpm) {
        $sql = "UPDATE user_records 
                SET record_value = :wpm, date_achieved = NOW() 
                WHERE user_id = :user_id AND record_type = :record_type";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":record_type", $wpm_record_type, PDO::PARAM_STR);
        $stmt->bindParam(":wpm", $wpm, PDO::PARAM_INT);
        $stmt->execute();
        error_log("Updated $language WPM record to $wpm");
    }
    
    // Record de précision
    // Récupérer le nombre total de parties du joueur (après l'ajout de la partie actuelle)
    $sql = "SELECT COUNT(*) as total_games FROM game_history WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_games = max(1, $stmt->fetchColumn()); // Au moins 1 partie

    // Calculer la précision moyenne
    $sql = "SELECT AVG(precision_score) as avg_precision FROM game_history WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $avg_precision = $stmt->fetchColumn();

    // Formule de pondération : (Précision moyenne × (1 - 1/√nombre_parties))
    // Cette formule donne plus de poids à la précision à mesure que le nombre de parties augmente
    // Elle pénalise les joueurs avec peu de parties tout en récompensant ceux avec beaucoup de parties
    $confidence_factor = 1 - (1 / sqrt(min(100, $total_games))); // Limité à 100 parties pour le facteur
    $weighted_precision = $avg_precision * $confidence_factor;

    // Récupérer le record de précision pondérée actuel
    $sql = "SELECT record_value FROM user_records 
            WHERE user_id = :user_id AND record_type = 'weighted_precision'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_weighted_precision = $stmt->fetchColumn();

    // Mettre à jour si meilleur record
    if ($weighted_precision > $current_weighted_precision) {
        $sql = "UPDATE user_records 
                SET record_value = :weighted_precision, date_achieved = NOW() 
                WHERE user_id = :user_id AND record_type = 'weighted_precision'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":weighted_precision", $weighted_precision, PDO::PARAM_STR);
        $stmt->execute();
        error_log("Updated weighted precision record to $weighted_precision");
    }
    
    // 3. Mettre à jour les statistiques moyennes de l'utilisateur
    $sql = "SELECT AVG(precision_score) as avg_precision, AVG(errors) as avg_errors, COUNT(*) as total_games
            FROM game_history WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetch();
    
    $sql = "UPDATE users 
            SET avg_precision = :avg_precision, avg_errors = :avg_errors, total_games = :total_games 
            WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":avg_precision", $stats['avg_precision'], PDO::PARAM_STR);
    $stmt->bindParam(":avg_errors", $stats['avg_errors'], PDO::PARAM_INT);
    $stmt->bindParam(":total_games", $stats['total_games'], PDO::PARAM_INT);
    $stmt->execute();
    
    // Valider la transaction
    $pdo->commit();
    
    // Retourner une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true, 
        "message" => "Résultats enregistrés avec succès.",
        "debug" => [
            "wpm" => $wpm,
            "precision" => $precision,
            "errors" => $errors,
            "stats" => $stats
        ]
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    $pdo->rollBack();
    
    error_log("Error saving game: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Erreur lors de l'enregistrement des résultats: " . $e->getMessage()]);
}

exit;
?>