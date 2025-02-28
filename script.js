// Sélection des éléments du DOM
const textDisplay = document.getElementById('text-display');
const inputField = document.getElementById('input-field');
const restartBtn = document.getElementById('restart-btn');
const wpmDisplay = document.getElementById('wpm');
const accuracyDisplay = document.getElementById('accuracy');
const errorsDisplay = document.getElementById('errors');
const minutesDisplay = document.getElementById('minutes');
const secondsDisplay = document.getElementById('seconds');
const frBtn = document.getElementById('fr-btn');
const enBtn = document.getElementById('en-btn');

// Listes de secours en cas d'échec de l'API
const commonFrenchWords = [
    "le", "la", "un", "une", "et", "est", "en", "que", "qui", "dans", "pour", "sur", "avec", "pas", 
    "des", "ce", "se", "il", "elle", "sont", "au", "du", "mais", "ou", "car", "donc", "alors", "si", 
    "tout", "plus", "moins", "aussi", "très", "bien", "mal", "bon", "bonne", "grand", "grande", "petit",
    "jour", "monde", "vie", "temps", "main", "chose", "homme", "femme", "enfant", "pays", "ville", "rue",
    "maison", "travail", "famille", "ami", "père", "mère", "fils", "fille", "chef", "idée", "corps", "tête",
    "cœur", "eau", "terre", "air", "feu", "mer", "montagne", "arbre", "fleur", "animal", "chien", "chat",
    "voiture", "train", "avion", "bateau", "route", "pont", "porte", "fenêtre", "table", "chaise", "lit",
    "cuisine", "salon", "chambre", "salle", "jardin", "parc", "école", "travail", "bureau", "magasin",
    "restaurant", "café", "hôtel", "hôpital", "médecin", "professeur", "étudiant", "livre", "journal",
    "histoire", "film", "musique", "chanson", "danse", "sport", "jeu", "jouet", "argent", "prix", "nombre",
    "couleur", "rouge", "bleu", "vert", "jaune", "noir", "blanc", "gris", "brun", "rose", "orange",
    "chaud", "froid", "nouveau", "vieux", "jeune", "beau", "joli", "fort", "faible", "rapide", "lent",
    "facile", "difficile", "simple", "complexe", "important", "utile", "possible", "impossible", "vrai", "faux",
    "matin", "midi", "soir", "nuit", "aujourd'hui", "demain", "hier", "semaine", "mois", "année", "siècle",
    "heure", "minute", "seconde", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août",
    "septembre", "octobre", "novembre", "décembre", "lundi", "mardi", "mercredi", "jeudi", "vendredi",
    "samedi", "dimanche", "nord", "sud", "est", "ouest", "haut", "bas", "gauche", "droite", "devant", "derrière"
];

const commonEnglishWords = [
    "the", "be", "to", "of", "and", "a", "in", "that", "have", "I", "it", "for", "not", "on", "with", 
    "he", "as", "you", "do", "at", "this", "but", "his", "by", "from", "they", "we", "say", "her", "she", 
    "or", "an", "will", "my", "one", "all", "would", "there", "their", "what", "so", "up", "out", "if"
];

// Variables pour le suivi des statistiques
let timer;
let timeLeft = 60; // 1 minute par défaut
let isTestActive = false;
let errors = 0;
let accuracy = 0;
let wpm = 0;
let currentText = "";
let currentWordIndex = 0;
let startTime;
let totalTypedWords = 0;
let correctWords = 0;
let currentLanguage = 'french'; // Langue par défaut

// Fonction pour récupérer des mots aléatoires depuis l'API Datamuse
async function fetchRandomWords(count = 30, language = 'fr') {
    try {
        let apiUrl;
        
        if (language === 'fr') {
            // API pour les mots français - Random Word Generator API
            apiUrl = `https://random-word.ryanrk.com/api/fr/word/random?number=${count}`;
        } else {
            // API pour les mots anglais - Random Word API
            apiUrl = `https://random-word-api.herokuapp.com/word?number=${count}`;
        }
        
        console.log(`Tentative de récupération de mots depuis: ${apiUrl}`);
        
        // Ajouter un timeout pour éviter d'attendre indéfiniment
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 secondes de timeout
        
        const response = await fetch(apiUrl, { signal: controller.signal });
        clearTimeout(timeoutId);
        
        // Vérifier si la réponse est OK
        if (!response.ok) {
            console.warn(`Réponse API non valide (${response.status}), utilisation de la liste de secours`);
            return useFallbackWordList(count, language);
        }
        
        const data = await response.json();
        console.log("Réponse de l'API:", data);
        
        // Vérifier si les données reçues sont valides
        if (!data || (Array.isArray(data) && data.length < count)) {
            console.warn("Données insuffisantes depuis l'API, utilisation de la liste de secours");
            return useFallbackWordList(count, language);
        }
        
        let words;
        // Traitement des données selon le format de l'API
        if (Array.isArray(data)) {
            // Si la réponse est déjà un tableau de mots
            words = data.slice(0, count);
        } else if (typeof data === 'object') {
            // Si la réponse est un objet avec une propriété contenant les mots
            // Adapter selon la structure réelle de la réponse
            const possibleArrayProperties = Object.values(data).filter(Array.isArray);
            if (possibleArrayProperties.length > 0) {
                words = possibleArrayProperties[0].slice(0, count);
            } else {
                console.warn("Format de données inattendu, utilisation de la liste de secours");
                return useFallbackWordList(count, language);
            }
        } else {
            console.warn("Format de données inattendu, utilisation de la liste de secours");
            return useFallbackWordList(count, language);
        }
        
        // Vérification finale et filtrage des mots non valides
        words = words
            .filter(word => word && typeof word === 'string' && word.length > 1)
            .map(word => word.toLowerCase().trim());
        
        if (words.length < count) {
            // Compléter avec des mots de secours si nécessaire
            const fallbackWords = useFallbackWordList(count - words.length, language);
            words = [...words, ...fallbackWords];
        }
        
        // Log pour confirmer que nous utilisons bien les mots de l'API
        console.log("Mots effectivement utilisés:", words);
        return words;
        
    } catch (error) {
        console.error('Erreur lors de la récupération des mots:', error);
        return useFallbackWordList(count, language);
    }
}
// Fonction de secours qui utilise les listes locales en cas d'échec de l'API
function useFallbackWordList(count, language) {
    console.log("Using fallback word list for language:", language);
    const wordList = language === 'fr' ? commonFrenchWords : commonEnglishWords;
    const selectedWords = [];
    
    for (let i = 0; i < count; i++) {
        const randomIndex = Math.floor(Math.random() * wordList.length);
        selectedWords.push(wordList[randomIndex]);
    }
    
    return selectedWords;
}

// Fonction pour initialiser le test
async function initTest() {
    console.log("Initialisation du test avec la langue:", currentLanguage);
    
    inputField.disabled = true;
    textDisplay.innerHTML = '<span class="loading">Chargement des mots...</span>';
    
    try {
        // Récupérer des mots aléatoires
        const lang = currentLanguage === 'french' ? 'fr' : 'en';
        console.log(`Récupération de mots pour la langue: ${lang}`);
        
        const words = await fetchRandomWords(30, lang);
        
        // Vérification explicite que nous n'utilisons pas les listes par défaut
        const frenchDefaultWords = commonFrenchWords.slice(0, 5).join(',');
        const englishDefaultWords = commonEnglishWords.slice(0, 5).join(',');
        const firstFiveWords = words.slice(0, 5).join(',');
        
        if ((lang === 'fr' && firstFiveWords === frenchDefaultWords) || 
            (lang === 'en' && firstFiveWords === englishDefaultWords)) {
            console.warn("⚠️ Utilisation des mots par défaut détectée!");
        } else {
            console.log("✓ Utilisation de mots aléatoires confirmée");
        }
        
        currentText = words.join(' ');
        
        // Afficher les mots
        textDisplay.innerHTML = words.map((word, index) => 
            `<span id="word-${index}" class="${index === 0 ? 'current-word' : ''}">${word}</span>`
        ).join(' ');
        
        // Réinitialisation des variables
        currentWordIndex = 0;
        isTestActive = false;
        errors = 0;
        totalTypedWords = 0;
        correctWords = 0;
        accuracy = 0;
        wpm = 0;
        timeLeft = 60;
        inputField.value = '';
        
        // Réactiver le champ de saisie
        inputField.disabled = false;
        
        // Mise à jour de l'affichage
        updateDisplay();
        
        // Focus sur le champ de saisie
        inputField.focus();
    } catch (error) {
        console.error('Échec de l\'initialisation du test:', error);
        textDisplay.innerHTML = '<span class="error">Erreur lors du chargement des mots. Veuillez réessayer.</span>';
        inputField.disabled = false;
    }
}

// Fonction pour mettre à jour l'affichage des statistiques
function updateDisplay() {
    wpmDisplay.textContent = wpm;
    accuracyDisplay.textContent = accuracy + "%";
    errorsDisplay.textContent = errors;
    minutesDisplay.textContent = Math.floor(timeLeft / 60);
    secondsDisplay.textContent = (timeLeft % 60).toString().padStart(2, '0');
}

// Fonction pour lancer le chronomètre
function startTimer() {
    if (!isTestActive) {
        isTestActive = true;
        startTime = new Date().getTime();
        timer = setInterval(() => {
            timeLeft--;
            updateDisplay();
            
            // Calcul du WPM en temps réel
            const elapsedTime = (new Date().getTime() - startTime) / 60000; // en minutes
            if (elapsedTime > 0) {
                wpm = Math.round(correctWords / elapsedTime);
                updateDisplay();
            }
            
            if (timeLeft <= 0) {
                endTest();
            }
        }, 1000);
    }
}

// Fonction pour terminer le test
// Modification dans la fonction endTest de script.js
function endTest() {
    // Éviter de terminer le test plusieurs fois
    if (!isTestActive) return;
    
    clearInterval(timer);
    isTestActive = false;
    inputField.disabled = true;
    
    // Calcul des statistiques finales
    const elapsedTime = (new Date().getTime() - startTime) / 60000; // en minutes
    wpm = Math.round(correctWords / elapsedTime);
    accuracy = Math.round((correctWords / totalTypedWords) * 100) || 0;
    
    updateDisplay();
    
    // Si l'utilisateur est connecté, tenter de sauvegarder les résultats
    // Ajouter un drapeau pour éviter les sauvegardes multiples
    if (document.body.classList.contains('logged-in') && !endTest.saved) {
        endTest.saved = true;
        saveGameResults();
    }
}

// Fonction pour vérifier le mot en cours
function checkCurrentWord() {
    const wordElements = textDisplay.querySelectorAll('span');
    const currentWord = wordElements[currentWordIndex].textContent;
    const typedWord = inputField.value.trim();
    
    totalTypedWords++;
    
    if (typedWord === currentWord) {
        // Mot correct
        wordElements[currentWordIndex].classList.remove('current-word');
        wordElements[currentWordIndex].classList.add('correct-word');
        correctWords++;
        
        // Passer au mot suivant
        currentWordIndex++;
        if (currentWordIndex < wordElements.length) {
            wordElements[currentWordIndex].classList.add('current-word');
        } else {
            // Tous les mots ont été complétés
            endTest();
            return;
        }
    } else {
        // Mot incorrect
        wordElements[currentWordIndex].classList.add('incorrect-word');
        errors++;
        setTimeout(() => {
            wordElements[currentWordIndex].classList.remove('incorrect-word');
        }, 500);
    }
    
    // Réinitialiser le champ de saisie
    inputField.value = '';
    
    // Mettre à jour l'affichage
    updateDisplay();
}

// Gestionnaire d'événement pour la saisie
inputField.addEventListener('input', function(e) {
    // Démarrer le chronomètre à la première frappe
    if (!isTestActive && e.target.value.length === 1) {
        startTimer();
    }
});

// Gestionnaire d'événement pour les touches
inputField.addEventListener('keydown', function(e) {
    if (e.key === ' ' && inputField.value.trim() !== '') {
        e.preventDefault(); // Empêcher l'espace de s'ajouter
        checkCurrentWord();
    }
});

// Gestionnaires d'événements pour les boutons de langue
frBtn.addEventListener('click', function() {
    currentLanguage = 'french';
    frBtn.classList.add('active');
    enBtn.classList.remove('active');
    initTest();
});

enBtn.addEventListener('click', function() {
    currentLanguage = 'english';
    enBtn.classList.add('active');
    frBtn.classList.remove('active');
    initTest();
});

// Gestionnaire d'événement pour le bouton de redémarrage
restartBtn.addEventListener('click', function() {
    if (isTestActive) {
        clearInterval(timer);
    }
    inputField.disabled = false;
    initTest();
});

// Fonction pour sauvegarder les résultats (si l'utilisateur est connecté)
function saveGameResults() {
    const formData = new FormData();
    formData.append('wpm', wpm);
    formData.append('precision', accuracy);
    formData.append('errors', errors);
    formData.append('language', currentLanguage === 'french' ? 'french' : 'english');
    
    fetch('save_game.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
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

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', initTest);