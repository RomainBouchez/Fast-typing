<?php
// Inclure le fichier de configuration de la base de données
require_once 'database.php';

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Vérifier si le champ nom d'utilisateur est vide
    if (empty(trim($_POST["username"]))) {
        $username_err = "Veuillez entrer votre nom d'utilisateur.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Vérifier si le champ mot de passe est vide
    if (empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer votre mot de passe.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Valider les identifiants
    if (empty($username_err) && empty($password_err)) {
        // Préparer une requête SELECT
        $sql = "SELECT user_id, username, password FROM users WHERE username = :username";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = $username;
            
            if ($stmt->execute()) {
                // Vérifier si le nom d'utilisateur existe
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        $id = $row["user_id"];
                        $username = $row["username"];
                        $hashed_password = $row["password"];
                        
                        // Vérifier le mot de passe
                        if (password_verify($password, $hashed_password)) {
                            // Le mot de passe est correct, démarrer une nouvelle session
                            session_start();
                            
                            // Stocker les données dans les variables de session
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["loggedin"] = true;
                            
                            // Rediriger vers la page d'accueil
                            redirect("index.php");
                        } else {
                            // Le mot de passe n'est pas valide
                            $login_err = "Nom d'utilisateur ou mot de passe invalide.";
                        }
                    }
                } else {
                    // Le nom d'utilisateur n'existe pas
                    $login_err = "Nom d'utilisateur ou mot de passe invalide.";
                }
            } else {
                echo "Oups! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
            }
            
            unset($stmt);
        }
    }
    
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Test de Vitesse de Frappe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .btn-submit {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 15px;
        }
        
        .btn-submit:hover {
            background-color: #2980b9;
        }
        
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .register-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .global-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Connexion</h2>
        <p>Veuillez remplir vos identifiants pour vous connecter.</p>
        
        <?php 
        if (!empty($login_err)) {
            echo '<div class="global-error">' . $login_err . '</div>';
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="error-message"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="form-control">
                <span class="error-message"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-submit" value="Se connecter">
            </div>
            <p class="register-link">Vous n'avez pas de compte? <a href="register.php">Inscrivez-vous maintenant</a>.</p>
        </form>
    </div>
</body>
</html>