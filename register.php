<?php
// Inclure le fichier de configuration de la base de données
require_once 'database.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validation du nom d'utilisateur
    if (empty(trim($_POST["username"]))) {
        $username_err = "Veuillez entrer un nom d'utilisateur.";
    } else {
        // Préparer une requête SELECT
        $sql = "SELECT user_id FROM users WHERE username = :username";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST["username"]);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $username_err = "Ce nom d'utilisateur est déjà pris.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oups! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
            }
            
            unset($stmt);
        }
    }
    
    // Validation de l'email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Veuillez entrer un email.";
    } else {
        // Vérifier le format de l'email
        if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format d'email invalide.";
        } else {
            // Vérifier si l'email existe déjà
            $sql = "SELECT user_id FROM users WHERE email = :email";
            
            if ($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                $param_email = trim($_POST["email"]);
                
                if ($stmt->execute()) {
                    if ($stmt->rowCount() == 1) {
                        $email_err = "Cet email est déjà utilisé.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo "Oups! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
                }
                
                unset($stmt);
            }
        }
    }
    
    // Validation du mot de passe
    if (empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer un mot de passe.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Le mot de passe doit avoir au moins 6 caractères.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validation de la confirmation du mot de passe
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Veuillez confirmer le mot de passe.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Les mots de passe ne correspondent pas.";
        }
    }
    
    // Vérifier les erreurs avant d'insérer dans la base de données
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Préparer une requête d'insertion
        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hachage du mot de passe
            
            if ($stmt->execute()) {
                // Initialiser les records pour le nouvel utilisateur
                $user_id = $pdo->lastInsertId();
                
                // Initialiser les différents types de records
                $record_types = ['global_wpm', 'french_wpm', 'english_wpm', 'best_precision'];
                foreach ($record_types as $type) {
                    $sql = "INSERT INTO user_records (user_id, record_type, record_value) VALUES (:user_id, :record_type, 0)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(":record_type", $type, PDO::PARAM_STR);
                    $stmt->execute();
                }
                
                // Rediriger vers la page de connexion
                redirect("login.php");
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
    <title>Inscription - Test de Vitesse de Frappe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Inscription</h2>
        <p>Veuillez remplir ce formulaire pour créer un compte.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="error-message"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="error-message"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="form-control">
                <span class="error-message"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" class="form-control">
                <span class="error-message"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-submit" value="S'inscrire">
            </div>
            <p class="login-link">Vous avez déjà un compte? <a href="login.php">Connectez-vous ici</a>.</p>
        </form>
    </div>
</body>
</html>