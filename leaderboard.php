<?php
// Inclure le fichier de configuration
require_once "database.php";

// Récupérer les meilleurs scores pour chaque catégorie
try {
    // Classement global WPM
    $sql = "SELECT u.username, r.record_value as score, r.date_achieved 
            FROM user_records r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.record_type = 'global_wpm' AND r.record_value > 0
            ORDER BY r.record_value DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $global_wpm_ranking = $stmt->fetchAll();
    
    // Classement WPM en français
    $sql = "SELECT u.username, r.record_value as score, r.date_achieved 
            FROM user_records r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.record_type = 'french_wpm' AND r.record_value > 0
            ORDER BY r.record_value DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $french_wpm_ranking = $stmt->fetchAll();
    
    // Classement WPM en anglais
    $sql = "SELECT u.username, r.record_value as score, r.date_achieved 
            FROM user_records r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.record_type = 'english_wpm' AND r.record_value > 0
            ORDER BY r.record_value DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $english_wpm_ranking = $stmt->fetchAll();
    
    // Classement précision pondérée
    $sql = "SELECT u.username, r.record_value as score, r.date_achieved,
            (SELECT COUNT(*) FROM game_history WHERE user_id = u.user_id) as games_played
            FROM user_records r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.record_type = 'weighted_precision' AND r.record_value > 0
            ORDER BY r.record_value DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $precision_ranking = $stmt->fetchAll();
    
    // Récupérer les records globaux (le meilleur score tous joueurs confondus)
    $sql = "SELECT 
            'global_wpm' as type, 
            MAX(record_value) as max_score, 
            (SELECT username FROM users WHERE user_id = 
                (SELECT user_id FROM user_records WHERE record_type = 'global_wpm' 
                ORDER BY record_value DESC LIMIT 1)
            ) as champion,
            (SELECT date_achieved FROM user_records WHERE record_type = 'global_wpm' 
            ORDER BY record_value DESC LIMIT 1) as date_record,
            NULL as champion_games
        FROM user_records WHERE record_type = 'global_wpm'
        
        UNION
        
        SELECT 
            'french_wpm' as type, 
            MAX(record_value) as max_score,
            (SELECT username FROM users WHERE user_id = 
                (SELECT user_id FROM user_records WHERE record_type = 'french_wpm' 
                ORDER BY record_value DESC LIMIT 1)
            ) as champion,
            (SELECT date_achieved FROM user_records WHERE record_type = 'french_wpm' 
            ORDER BY record_value DESC LIMIT 1) as date_record,
            NULL as champion_games
        FROM user_records WHERE record_type = 'french_wpm'
        
        UNION
        
        SELECT 
            'english_wpm' as type, 
            MAX(record_value) as max_score,
            (SELECT username FROM users WHERE user_id = 
                (SELECT user_id FROM user_records WHERE record_type = 'english_wpm' 
                ORDER BY record_value DESC LIMIT 1)
            ) as champion,
            (SELECT date_achieved FROM user_records WHERE record_type = 'english_wpm' 
            ORDER BY record_value DESC LIMIT 1) as date_record,
            NULL as champion_games
        FROM user_records WHERE record_type = 'english_wpm'
        
        UNION
        
        SELECT 
            'weighted_precision' as type, 
            MAX(record_value) as max_score, 
            (SELECT username FROM users WHERE user_id = 
                (SELECT user_id FROM user_records WHERE record_type = 'weighted_precision' 
                ORDER BY record_value DESC LIMIT 1)
            ) as champion,
            (SELECT date_achieved FROM user_records WHERE record_type = 'weighted_precision' 
            ORDER BY record_value DESC LIMIT 1) as date_record,
            (SELECT COUNT(*) FROM game_history WHERE user_id = 
                (SELECT user_id FROM user_records WHERE record_type = 'weighted_precision' 
                ORDER BY record_value DESC LIMIT 1)
            ) as champion_games
        FROM user_records WHERE record_type = 'weighted_precision'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $global_records = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des classements: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement - Test de Vitesse de Frappe</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="leaderboard-container">
        <div class="leaderboard-header">
            <h2>Classement des Meilleurs Joueurs</h2>
            <div class="links">
                <a href="index.php">Retour au jeu</a>
                <?php if (is_logged_in()): ?>
                    <a href="profile.php">Mon Profil</a>
                <?php else: ?>
                    <a href="login.php">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php else: ?>
            
            <div class="hall-of-fame">
                <h3>🏆 HALL OF FAME 🏆</h3>
                <div class="records-grid">
                    <?php foreach ($global_records as $record): ?>
                        <?php if (empty($record['champion']) || $record['max_score'] <= 0) continue; ?>
                        <?php 
                            $date = new DateTime($record['date_record']);
                            $date_formatted = $date->format('d/m/Y H:i');
                            
                            // Formater le nom et la valeur du record
                            switch ($record['type']) {
                                case 'global_wpm':
                                    $record_name = 'Record WPM global';
                                    $record_value = $record['max_score'] . ' WPM';
                                    break;
                                case 'french_wpm':
                                    $record_name = 'Record WPM en français';
                                    $record_value = $record['max_score'] . ' WPM';
                                    break;
                                case 'english_wpm':
                                    $record_name = 'Record WPM en anglais';
                                    $record_value = $record['max_score'] . ' WPM';
                                    break;
                                case 'weighted_precision':
                                    $record_name = 'Score de Précision Élite';
                                    $precision_value = number_format($record['max_score'], 2);
                                    
                                    // Vérifier si champion_games existe avant de l'utiliser
                                    if (isset($record['champion_games']) && $record['champion_games'] > 0) {
                                        $confidence_factor = 1 - (1 / sqrt(min(100, $record['champion_games'])));
                                        if ($confidence_factor > 0) {
                                            $raw_precision = ($record['max_score'] / $confidence_factor);
                                            $raw_precision = number_format($raw_precision, 2);
                                            $record_value = $precision_value . ' points <small>(' . $raw_precision . '% sur ' . $record['champion_games'] . ' parties)</small>';
                                        } else {
                                            $record_value = $precision_value . ' points';
                                        }
                                    } else {
                                        $record_value = $precision_value . ' points';
                                    }
                                    break;
                                default:
                                    $record_name = $record['type'];
                                    $record_value = $record['max_score'];
                            }
                        ?>
                        <div class="record-card">
                            <div class="record-type"><?php echo $record_name; ?></div>
                            <div class="record-value"><?php echo $record_value; ?></div>
                            <div class="record-holder">Champion: <?php echo htmlspecialchars($record['champion']); ?></div>
                            <div class="record-date">Réalisé le <?php echo $date_formatted; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="tab-selector">
                <button class="tab-btn active" data-tab="global">WPM Global</button>
                <button class="tab-btn" data-tab="french">WPM Français</button>
                <button class="tab-btn" data-tab="english">WPM Anglais</button>
                <button class="tab-btn" data-tab="precision">Précision</button>
            </div>
            
            <!-- Classement WPM Global -->
            <div id="global-tab" class="tab-content active">
                <h3 class="section-title">Classement WPM Global</h3>
                <?php if (count($global_wpm_ranking) > 0): ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th class="rank-number">Rang</th>
                                <th>Joueur</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($global_wpm_ranking as $index => $player): ?>
                                <?php 
                                    $rank_class = '';
                                    if ($index === 0) $rank_class = 'first-place';
                                    elseif ($index === 1) $rank_class = 'second-place';
                                    elseif ($index === 2) $rank_class = 'third-place';
                                    
                                    $date = new DateTime($player['date_achieved']);
                                    $date_formatted = $date->format('d/m/Y H:i');
                                ?>
                                <tr class="<?php echo $rank_class; ?>">
                                    <td class="rank-number"><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($player['username']); ?></td>
                                    <td><?php echo $player['score']; ?> WPM</td>
                                    <td><?php echo $date_formatted; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucun score enregistré pour cette catégorie.</div>
                <?php endif; ?>
            </div>
            
            <!-- Classement WPM Français -->
            <div id="french-tab" class="tab-content">
                <h3 class="section-title">Classement WPM Français</h3>
                <?php if (count($french_wpm_ranking) > 0): ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th class="rank-number">Rang</th>
                                <th>Joueur</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($french_wpm_ranking as $index => $player): ?>
                                <?php 
                                    $rank_class = '';
                                    if ($index === 0) $rank_class = 'first-place';
                                    elseif ($index === 1) $rank_class = 'second-place';
                                    elseif ($index === 2) $rank_class = 'third-place';
                                    
                                    $date = new DateTime($player['date_achieved']);
                                    $date_formatted = $date->format('d/m/Y H:i');
                                ?>
                                <tr class="<?php echo $rank_class; ?>">
                                    <td class="rank-number"><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($player['username']); ?></td>
                                    <td><?php echo $player['score']; ?> WPM</td>
                                    <td><?php echo $date_formatted; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucun score enregistré pour cette catégorie.</div>
                <?php endif; ?>
            </div>
            
            <!-- Classement WPM Anglais -->
            <div id="english-tab" class="tab-content">
                <h3 class="section-title">Classement WPM Anglais</h3>
                <?php if (count($english_wpm_ranking) > 0): ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th class="rank-number">Rang</th>
                                <th>Joueur</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($english_wpm_ranking as $index => $player): ?>
                                <?php 
                                    $rank_class = '';
                                    if ($index === 0) $rank_class = 'first-place';
                                    elseif ($index === 1) $rank_class = 'second-place';
                                    elseif ($index === 2) $rank_class = 'third-place';
                                    
                                    $date = new DateTime($player['date_achieved']);
                                    $date_formatted = $date->format('d/m/Y H:i');
                                ?>
                                <tr class="<?php echo $rank_class; ?>">
                                    <td class="rank-number"><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($player['username']); ?></td>
                                    <td><?php echo $player['score']; ?> WPM</td>
                                    <td><?php echo $date_formatted; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucun score enregistré pour cette catégorie.</div>
                <?php endif; ?>
            </div>
            
            <!-- Classement Précision -->
            <div id="precision-tab" class="tab-content">
                <h3 class="section-title">Classement Score de Précision Élite</h3>
                <p class="precision-formula">
                    Le Score de Précision Élite est calculé avec la formule : 
                    <strong>Précision moyenne × (1 - 1/√nombre_parties)</strong>.
                    <br>Cette formule équilibre la précision et l'expérience, donnant plus de poids aux joueurs constants sur plusieurs parties.
                </p>
                <?php if (count($precision_ranking) > 0): ?>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th class="rank-number">Rang</th>
                                <th>Joueur</th>
                                <th>Score</th>
                                <th>Précision réelle</th>
                                <th>Parties</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($precision_ranking as $index => $player): ?>
                                <?php 
                                    $rank_class = '';
                                    if ($index === 0) $rank_class = 'first-place';
                                    elseif ($index === 1) $rank_class = 'second-place';
                                    elseif ($index === 2) $rank_class = 'third-place';
                                    
                                    $date = new DateTime($player['date_achieved']);
                                    $date_formatted = $date->format('d/m/Y H:i');
                                    
                                    // Calculer la précision réelle à partir du score pondéré
                                    $confidence_factor = 1 - (1 / sqrt(min(100, $player['games_played'])));
                                    $real_precision = ($confidence_factor > 0) ? ($player['score'] / $confidence_factor) : $player['score'];
                                ?>
                                <tr class="<?php echo $rank_class; ?>">
                                    <td class="rank-number"><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($player['username']); ?></td>
                                    <td><?php echo number_format($player['score'], 2); ?> pts</td>
                                    <td><?php echo number_format($real_precision, 2); ?>%</td>
                                    <td><?php echo $player['games_played']; ?></td>
                                    <td><?php echo $date_formatted; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucun score enregistré pour cette catégorie.</div>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
        
        <a href="index.php" class="back-btn">Retour au jeu</a>
    </div>
    
    <script>
        // Script pour gérer les onglets
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Désactiver tous les onglets
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Activer l'onglet sélectionné
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
        });
    </script>
</body>
</html>