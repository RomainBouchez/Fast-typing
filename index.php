<?php
// Inclure le fichier de configuration
require_once "database.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Vitesse de Frappe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-menu {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .user-menu .btn {
            padding: 8px 15px;
            margin-left: 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .login-btn, .register-btn {
            background-color: #3498db;
            color: white;
            border: none;
        }
        
        .profile-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
        }
        
        .logout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
        
        .leaderboard-btn {
            background-color: #f39c12;
            color: white;
            border: none;
        }
        
        .welcome-message {
            font-size: 14px;
            color: #7f8c8d;
            margin-right: 15px;
            display: flex;
            align-items: center;
        }

        .welcome-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: rgba(42, 42, 42, 0.9);
            color: #e0e0e0;
            border-radius: 5px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            animation: fadeInOut 5s forwards;
            display: flex;
            align-items: center;
        }
        
        .welcome-popup .user-icon {
            margin-right: 10px;
            font-size: 18px;
            color: #4db6e2;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-20px); }
            10% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        .user-menu {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
        
        .user-menu .btn {
            padding: 8px 15px;
            margin-left: 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        
        /* Styles pour les notifications */
        .save-success-message,
        .save-error-message,
        .login-prompt {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            z-index: 1000;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease-out;
        }

        .save-success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .save-error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-content {
            display: flex;
            align-items: center;
        }

        .message-content span {
            font-size: 20px;
            margin-right: 10px;
        }

        .login-prompt {
            background-color: white;
            width: 400px;
            padding: 20px;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .prompt-content h3 {
            margin-top: 0;
            color: #2c3e50;
        }

        .prompt-buttons {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .login-btn,
        .register-btn,
        .close-btn {
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .close-btn {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }

        .replay-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .replay-btn:hover {
            background-color: #27ae60;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="<?php echo is_logged_in() ? 'logged-in' : ''; ?>">

    <?php if (is_logged_in()): ?>
    <div class="welcome-popup">
        <span class="user-icon">👤</span>
        <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?> !</span>
    </div>
    <?php endif; ?>

    <div class="container">
        <h1>Test de Vitesse de Frappe</h1>
        
        <div class="user-menu">
            <?php if (is_logged_in()): ?>
                <!-- Enlever le message de bienvenue du menu -->
                <a href="profile.php" class="btn profile-btn">Mon Profil</a>
                <a href="leaderboard.php" class="btn leaderboard-btn">Classement</a>
                <a href="logout.php" class="btn logout-btn">Déconnexion</a>
            <?php else: ?>
                <a href="leaderboard.php" class="btn leaderboard-btn">Classement</a>
                <a href="login.php" class="btn login-btn">Connexion</a>
                <a href="register.php" class="btn register-btn">Inscription</a>
            <?php endif; ?>
        </div>

        <div class="language-selector">
            <button id="fr-btn" class="language-btn active">Français</button>
            <button id="en-btn" class="language-btn">English</button>
        </div>
        
        <div class="timer">
            <div id="minutes">1</div>
            <span>:</span>
            <div id="seconds">00</div>
        </div>
        
        <div class="test-container">
            <div id="text-display" class="text-display">
                <!-- Le texte à taper apparaîtra ici -->
            </div>
            
            <textarea id="input-field" class="input-field" placeholder="Commencez à taper quand vous êtes prêt..."></textarea>
        </div>
        
        <div class="stats-container">
            <div class="stat">
                <div class="stat-value" id="wpm">0</div>
                <div class="stat-label">WPM</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="accuracy">0</div>
                <div class="stat-label">Précision</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="errors">0</div>
                <div class="stat-label">Erreurs</div>
            </div>
        </div>
        
        <button id="restart-btn">Recommencer</button>
    </div>

    <script src="script.js"></script>
    <script>
        // Code JavaScript spécifique à l'intégration de l'authentification
        
        // Fonction pour vérifier si l'utilisateur est connecté
        function isLoggedIn() {
            return document.body.classList.contains('logged-in');
        }
        
        // Fonction pour sauvegarder les résultats
        function saveGameResults() {
            // Vérifier si l'utilisateur est connecté
            if (!isLoggedIn()) {
                // Afficher un message pour inciter à se connecter
                showLoginPrompt();
                return;
            }
            
            // Préparer les données à envoyer
            const formData = new FormData();
            formData.append('wpm', wpm);
            formData.append('precision', accuracy);
            formData.append('errors', errors);
            formData.append('language', currentLanguage === 'french' ? 'french' : 'english');
            
            // Envoyer les données au serveur
            fetch('save_game.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin' // Pour envoyer les cookies de session
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveSuccess();
                } else {
                    showSaveError(data.message);
                }
            })
            .catch(error => {
                console.error('Error saving game results:', error);
                showSaveError('Une erreur est survenue lors de la sauvegarde des résultats.');
            });
        }
        
        // Fonction pour afficher un message de succès
        function showSaveSuccess() {
            const messageElement = document.createElement('div');
            messageElement.className = 'save-success-message';
            messageElement.innerHTML = `
                <div class="message-content">
                    <span>✓</span>
                    <p>Résultats sauvegardés avec succès!</p>
                </div>
            `;
            
            document.body.appendChild(messageElement);
            
            setTimeout(() => {
                messageElement.remove();
            }, 3000);
        }
        
        // Fonction pour afficher une erreur de sauvegarde
        function showSaveError(message) {
            const messageElement = document.createElement('div');
            messageElement.className = 'save-error-message';
            messageElement.innerHTML = `
                <div class="message-content">
                    <span>⚠</span>
                    <p>${message}</p>
                </div>
            `;
            
            document.body.appendChild(messageElement);
            
            setTimeout(() => {
                messageElement.remove();
            }, 5000);
        }
        
        // Fonction pour afficher une invite de connexion
        function showLoginPrompt() {
            const promptElement = document.createElement('div');
            promptElement.className = 'login-prompt';
            promptElement.innerHTML = `
                <div class="prompt-content">
                    <h3>Connectez-vous pour sauvegarder vos résultats</h3>
                    <p>La connexion vous permet de suivre votre progression et de comparer vos performances.</p>
                    <div class="prompt-buttons">
                        <a href="login.php" class="login-btn">Connexion</a>
                        <a href="register.php" class="register-btn">Inscription</a>
                        <button class="close-btn">Plus tard</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(promptElement);
            
            promptElement.querySelector('.close-btn').addEventListener('click', () => {
                promptElement.remove();
            });
        }
        
        // Modifier la fonction endTest pour appeler saveGameResults
        const originalEndTest = endTest;
        endTest = function() {
            // Appeler la fonction originale
            originalEndTest();
            
            // Sauvegarder les résultats si l'utilisateur est connecté
            if (isLoggedIn()) {
                saveGameResults();
            } else {
                // Proposer de se connecter après quelques secondes
                setTimeout(() => {
                    showLoginPrompt();
                }, 1500);
            }
        };
    </script>