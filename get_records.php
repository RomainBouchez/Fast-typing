<?php
// Inclure le fichier de configuration
require_once "database.php";

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    // Renvoyer une réponse JSON indiquant que l'utilisateur n'est pas connecté
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Vous devez être connecté pour consulter vos records."]);
    exit;
}

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

// Récupérer les records de l'utilisateur
try {
    $sql = "SELECT record_type, record_value, date_achieved 
            FROM user_records 
            WHERE user_id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $records = [];
    while ($row = $stmt->fetch()) {
        $date = new DateTime($row['date_achieved']);
        $formatted_date = $date->format('d/m/Y H:i');
        
        // Formater les noms de records pour l'affichage
        switch ($row['record_type']) {
            case 'global_wpm':
                $display_name = 'Meilleur WPM global';
                break;
            case 'french_wpm':
                $display_name = 'Meilleur WPM en français';
                break;
            case 'english_wpm':
                $display_name = 'Meilleur WPM en anglais';
                break;
            case 'best_precision':
                $display_name = 'Meilleure précision';
                $row['record_value'] = number_format($row['record_value'], 2) . '%';
                break;
            default:
                $display_name = $row['record_type'];
        }
        
        $records[] = [
            'type' => $row['record_type'],
            'display_name' => $display_name,
            'value' => $row['record_value'],
            'date' => $formatted_date
        ];
    }
    
    // Récupérer également les statistiques générales de l'utilisateur
    $sql = "SELECT username, avg_precision, avg_errors, total_games 
            FROM users 
            WHERE user_id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $user_stats = $stmt->fetch();
    
    $stats = [
        'username' => $user_stats['username'],
        'avg_precision' => number_format($user_stats['avg_precision'], 2) . '%',
        'avg_errors' => round($user_stats['avg_errors']),
        'total_games' => $user_stats['total_games']
    ];
    
    // Retourner les records et les stats en format JSON
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true, 
        "records" => $records,
        "stats" => $stats
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Erreur lors de la récupération des records: " . $e->getMessage()]);
}

exit;
?>