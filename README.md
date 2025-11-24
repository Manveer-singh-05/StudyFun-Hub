🎓 StudyFun Hub

A fun, interactive, gamified e-learning platform designed to make education engaging, accessible, and student-friendly.

🚀 Overview

StudyFun Hub transforms traditional learning into an enjoyable digital experience. With quizzes, challenges, personalized profiles, progress tracking, and course modules, students can learn while having fun.

Built using PHP, MySQL, HTML, CSS, Bootstrap, and JavaScript, the platform features complete user authentication, profile management, and secure password reset functionality.

✅ Key Features
🔐 Authentication & Security

User Registration & Login

Profile Management with Profile Picture Upload

Forgot Password & Secure Token-Based Reset System

Session-Based Access Control

📚 Learning Features

Courses Module

Leaderboard

Subject Challenges (Math, English, Science, Programming)

Gamified Learning Experience

👤 User Dashboard

Personalized Homepage

Profile Image & Bio

Last Login Tracking

📨 Communication & Engagement

Contact Form (stored in database)

Feedback System (stored in database)

Email Subscription System (stored in database)

Success & Error Alerts

🌐 UI/UX Highlights

Modern Bootstrap UI

Mobile Responsive Design

Interactive Navbar & Footer

Default Profile Image Handling

🗄️ Database Modules
Table Name	Purpose
users	Stores user accounts & profile details
password_reset_tokens	Handles secure password resets
feedback	Stores user feedback
contact_messages	Stores contact form submissions
subscribers	Stores newsletter subscribers
🛠️ Tech Stack

Frontend

HTML5, CSS3, Bootstrap 5

JavaScript

Backend

PHP 8

MySQL (XAMPP / phpMyAdmin)

Tools

Git & GitHub

Visual Studio Code

📂 Project Structure
StudyFunHub/
│
├── IMG/                      # Images & profile pictures
├── Courses.html/.php         # Courses page
├── Contact.php               # Contact page
├── Leaderboard.php
├── Dashboard.php
├── profile-settings.php
├── subscribe.php
├── send_contact.php
├── save_feedback.php
├── reset_password.php
├── forgotpassword.php
├── navbar-loggedin.php
├── footer.php (or included footer code)
└── README.md

⚙️ Installation & Setup
✅ Requirements

XAMPP or WAMP

PHP 8+

MySQL Database

✅ Steps

Clone the repository:

git clone https://github.com/YOUR-USERNAME/StudyFun-Hub.git


Import the database tables into MySQL

Configure database credentials in files using:

$conn = new mysqli("localhost:3037", "root", "", "nps_elearning");


Start Apache & MySQL

Run the project:

http://localhost/StudyFun-Hub/

🧪 Testing the Password Reset System

Click Forgot Password

Enter registered email

Open reset link

Set new password

✅ Token-based
✅ Auto-expiry
✅ Secure hashing

👨‍💻 Developed By

Manveer Singh

📧 Email: (optional – add if you want)
🔗 GitHub: (optional link)

🏆 Future Enhancements

Admin Panel for managing users & data

Advanced Analytics & Reports

Badges & Reward System

Real-Time Notifications

AI-based recommendations

📜 License

This project is for educational and portfolio purposes. You may extend or modify it freely.

🌟 Final Note

StudyFun Hub isn’t just a project — it’s a complete learning ecosystem built with real-world functionality, clean UI, and secure backend workflows.

Proudly built from scratch ✅🔥
