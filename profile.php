<?php
// Inclure le fichier de configuration
require_once "database.php";

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    redirect("login.php");
    exit;
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    // Récupérer les statistiques de l'utilisateur
    $sql = "SELECT username, avg_precision, avg_errors, total_games FROM users WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_stats = $stmt->fetch();
    
    // Récupérer les records de l'utilisateur
    $sql = "SELECT record_type, record_value, date_achieved FROM user_records WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll();
    
    // Récupérer l'historique des 10 dernières parties
    $sql = "SELECT wpm, precision_score, errors, language, date_played 
            FROM game_history 
            WHERE user_id = :user_id 
            ORDER BY date_played DESC 
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Test de Vitesse de Frappe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .profile-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .profile-header .links a {
            margin-left: 15px;
            color: #3498db;
            text-decoration: none;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2980b9;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
        }
        
        .records-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .record-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 5px solid #3498db;
        }
        
        .record-value {
            font-size: 24px;
            font-weight: bold;
            color: #2980b9;
        }
        
        .record-type {
            font-size: 16px;
            color: #34495e;
            margin-bottom: 5px;
        }
        
        .record-date {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .history-table th, .history-table td {
            padding: 12px 15px;
            text-align: center;
        }
        
        .history-table th {
            background-color: #3498db;
            color: white;
            font-weight: normal;
        }
        
        .history-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .history-table tr:hover {
            background-color: #e0f0ff;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .back-to-game {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .back-to-game:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Profil de <?php echo htmlspecialchars($username); ?></h2>
            <div class="links">
                <a href="index.php">Retour au jeu</a>
                <a href="logout.php">Déconnexion</a>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php else: ?>
            
            <h3 class="section-title">Statistiques générales</h3>
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $user_stats['total_games']; ?></div>
                    <div class="stat-label">Parties jouées</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo number_format($user_stats['avg_precision'], 2); ?>%</div>
                    <div class="stat-label">Précision moyenne</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo round($user_stats['avg_errors']); ?></div>
                    <div class="stat-label">Erreurs moyennes</div>
                </div>
            </div>
            
            <h3 class="section-title">Mes records</h3>
            <?php if (count($records) > 0): ?>
                <div class="records-grid">
                    <?php foreach ($records as $record): ?>
                        <?php 
                            $date = new DateTime($record['date_achieved']);
                            $date_formatted = $date->format('d/m/Y H:i');
                            
                            // Formater les noms et valeurs des records
                            switch ($record['record_type']) {
                                case 'global_wpm':
                                    $record_name = 'Meilleur WPM global';
                                    $record_value = $record['record_value'] . ' WPM';
                                    break;
                                case 'french_wpm':
                                    $record_name = 'Meilleur WPM en français';
                                    $record_value = $record['record_value'] . ' WPM';
                                    break;
                                case 'english_wpm':
                                    $record_name = 'Meilleur WPM en anglais';
                                    $record_value = $record['record_value'] . ' WPM';
                                    break;
                                case 'best_precision':
                                    $record_name = 'Meilleure précision';
                                    $record_value = number_format($record['record_value'], 2) . '%';
                                    break;
                                default:
                                    $record_name = $record['record_type'];
                                    $record_value = $record['record_value'];
                            }
                            
                            // Afficher seulement les records avec des valeurs > 0
                            if ($record['record_value'] <= 0) continue;
                        ?>
                        <div class="record-card">
                            <div class="record-type"><?php echo $record_name; ?></div>
                            <div class="record-value"><?php echo $record_value; ?></div>
                            <div class="record-date">Réalisé le <?php echo $date_formatted; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">Aucun record enregistré. Jouez quelques parties pour voir vos records apparaître ici!</div>
            <?php endif; ?>
            
            <h3 class="section-title">Historique des 10 dernières parties</h3>
            <?php if (count($history) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>WPM</th>
                            <th>Précision</th>
                            <th>Erreurs</th>
                            <th>Langue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $game): ?>
                            <?php 
                                $date = new DateTime($game['date_played']);
                                $date_formatted = $date->format('d/m/Y H:i');
                                $language_display = ($game['language'] == 'french') ? 'Français' : 'Anglais';
                            ?>
                            <tr>
                                <td><?php echo $date_formatted; ?></td>
                                <td><?php echo $game['wpm']; ?></td>
                                <td><?php echo number_format($game['precision_score'], 2); ?>%</td>
                                <td><?php echo $game['errors']; ?></td>
                                <td><?php echo $language_display; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">Aucune partie enregistrée. Jouez quelques parties pour voir votre historique!</div>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <a href="index.php" class="back-to-game">Retour au jeu</a>
    </div>
</body>
</html>