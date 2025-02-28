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
    
    // Récupérer le nombre de parties jouées
    $sql = "SELECT COUNT(*) as games_count FROM game_history WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $games_count = $stmt->fetchColumn();
    
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Profil de <?php echo htmlspecialchars($username); ?></h2>
            <div class="links">
                <a href="leaderboard.php">Classement</a>
                <a href="index.php">Retour au jeu</a>
                <a href="logout.php">Déconnexion</a>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php else: ?>
            
            <h3 class="section-title">Statistiques générales</h3>
            <div class="stats-container">
                <div class="stat">
                    <div class="stat-value"><?php echo $user_stats['total_games']; ?></div>
                    <div class="stat-label">Parties jouées</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo number_format($user_stats['avg_precision'], 2); ?>%</div>
                    <div class="stat-label">Précision moyenne</div>
                </div>
                <div class="stat">
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
                                case 'weighted_precision':
                                    $record_name = 'Score de Précision Élite';
                                    // Calculer la précision réelle à partir du score pondéré
                                    $confidence_factor = 1 - (1 / sqrt(min(100, $games_count)));
                                    if ($confidence_factor > 0) {
                                        $real_precision = ($record['record_value'] / $confidence_factor);
                                        $record_value = number_format($record['record_value'], 2) . ' pts';
                                        $record_value .= '<div class="record-detail">(' . number_format($real_precision, 2) . 
                                            '% sur ' . $games_count . ' parties)</div>';
                                    } else {
                                        $record_value = number_format($record['record_value'], 2) . ' pts';
                                    }
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
                <table class="ranking-table">
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
        
        <a href="index.php" class="back-btn">Retour au jeu</a>
    </div>
    
    <script>
        // Script pour ajouter des effets visuels aux cartes de records
        document.addEventListener('DOMContentLoaded', function() {
            const recordCards = document.querySelectorAll('.record-card');
            
            recordCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.05)';
                });
            });
        });
    </script>
</body>
</html>