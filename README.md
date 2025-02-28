# Fast-typing

A web application for testing and improving typing speed, built with PHP, JavaScript, and MySQL.

## 📋 Overview

Fast-typing is a comprehensive typing test application that allows users to practice their typing speed and accuracy in both French and English. The application tracks user progress, maintains statistics, and provides a competitive environment for improving typing skills.

## ✨ Features

- **Real-time typing test** with immediate feedback
- **Bilingual support** - Practice in both French and English
- **Performance metrics** - WPM (Words Per Minute), accuracy percentage, and error count
- **User accounts** with authentication system
- **Statistics tracking** - Overall performance and historical data
- **Personal records** - Track your best performances
- **Responsive design** suitable for desktop and mobile devices

## 🔧 Technical Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **External API**: Datamuse API for word generation (with fallback to local word lists)

## 📁 Project Structure

```
fast-typing/
├── database.php      # Database connection and utility functions
├── get_history.php   # API for retrieving user game history
├── get_records.php   # API for retrieving user records
├── index.php         # Main application page
├── login.php         # User login page
├── logout.php        # Logout functionality
├── profile.php       # User profile page
├── register.php      # User registration page
├── save_game.php     # API for saving game results
├── script.js         # Main JavaScript functionality
├── style.css         # Application styling
```

## 🚀 Installation

1. **Clone the repository**:
   ```
   git clone https://github.com/yourusername/fast-typing.git
   cd fast-typing
   ```

2. **Database setup**:
   - Create a new MySQL database named `typing_game`
   - Import the database schema (see below)
   - Update database connection details in `database.php` if necessary

3. **Server setup**:
   - Set up a local web server (WAMP, XAMPP, etc.)
   - Place the project files in your web server's document root
   - Access the application through your web browser: `http://localhost/fast-typing`

## 📊 Database Schema

The application uses the following tables:

### `users`
- `user_id` - Primary key
- `username` - User's display name
- `email` - User's email address
- `password` - Hashed password
- `avg_precision` - Average precision score
- `avg_errors` - Average errors per game
- `total_games` - Total number of games played
- `date_created` - Account creation date

### `game_history`
- `game_id` - Primary key
- `user_id` - Foreign key to users
- `wpm` - Words per minute score
- `precision_score` - Accuracy percentage
- `errors` - Number of errors
- `language` - Language used (french/english)
- `date_played` - Game timestamp

### `user_records`
- `record_id` - Primary key
- `user_id` - Foreign key to users
- `record_type` - Type of record (global_wpm, french_wpm, english_wpm, best_precision)
- `record_value` - Record value
- `date_achieved` - Record achievement date

## 🔍 How To Use

1. **Register** for a new account or **log in** with existing credentials
2. Choose your preferred language (**French** or **English**)
3. Start typing in the text box to begin the test
4. The timer will automatically start on your first keystroke
5. Type the displayed words as quickly and accurately as possible
6. Press **Space** to move to the next word
7. After 60 seconds, the test will end automatically
8. View your results and statistics
9. Visit your profile page to track your progress over time

## 📈 Metrics Explained

- **WPM (Words Per Minute)**: The number of correctly typed words per minute
- **Precision**: The percentage of correctly typed words relative to total typed words
- **Errors**: The number of incorrectly typed words

## 🛠️ Development Notes

- Make sure to enable error reporting during development by adding the following to your PHP files:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

- Password storage uses PHP's `password_hash()` function with the default algorithm (currently BCRYPT)

- The application includes fallback word lists in case the Datamuse API is unavailable

## 🔒 Security Features

- Password hashing with PHP's secure password functions
- PDO prepared statements to prevent SQL injection
- Input sanitization for user data
- Session-based authentication system
- CSRF protection in forms

## 📱 Responsive Design

The application is designed to work across different screen sizes and devices with responsive CSS styling.

## 👥 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open source and available under the [MIT License](LICENSE).
