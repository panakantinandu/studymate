# 🎓 StudyMate – Peer Learning & Scheduling Platform  

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

A full-featured **peer learning and scheduling platform** built using **PHP + MySQL**, designed to help students and tutors collaborate through real-time study sessions, shared availability, and feedback.

---

## 🧭 System Architecture Overview

```text
         ┌─────────────────────┐
         │      Admin Panel    │
         │  - Manage users     │
         │  - Monitor sessions │
         │  - View analytics   │
         └─────────┬───────────┘
                   │
                   │
         ┌─────────▼───────────┐
         │     Application     │
         │  (PHP + MySQL)      │
         │─────────────────────│
         │ Routes / Controllers│
         │ Models / Views / DB │
         └─────────┬───────────┘
                   │
         ┌─────────▼───────────┐
         │      Database       │
         │     (MySQL)         │
         │─────────────────────│
         │ users               │
         │ availability         │
         │ session_requests     │
         │ notifications        │
         │ session_feedback     │
         │ subjects / user_subj │
         └─────────┬───────────┘
                   │
     ┌─────────────▼─────────────────┐
     │           Frontend            │
     │ (Bootstrap + HTML + CSS + JS) │
     │───────────────────────────────│
     │ Student Dashboard             │
     │ Tutor Dashboard               │
     │ Session Management            │
     │ Notifications & Ratings       │
     │ Video Meeting (Jitsi Link)    │
     └───────────────────────────────┘
````

**Flow Explanation**

1. Users register → select subjects → set availability
2. System matches available peers based on day & time
3. Session requests can be sent, accepted, or rejected
4. Upon acceptance → auto-generated **meeting link** (via Jitsi)
5. After the scheduled time → session auto-marks **completed**
6. Both users give **ratings and comments**
7. Admin can view system analytics, sessions, and feedback

---

## 🚀 Features

### 👩‍🎓 Student Features

* 📅 Set weekly availability
* 🔍 Match with peers or tutors
* 🤝 Send and receive session requests
* 🔗 Auto-generated meeting links
* 🕓 Auto session completion after time expires
* 🔔 Reminders & notifications
* ⭐ Give and receive ratings and feedback
* 📊 Dashboard summary: total sessions, completed, average rating

### 🧑‍🏫 Tutor Features

* Manage sessions with students
* Accept/Reject requests
* Join live study sessions via meeting link
* Receive performance feedback

### 🧑‍💼 Admin Panel

* Manage users, subjects, and sessions
* Monitor ratings and analytics
* Handle reports and feedback

---

## 🗂️ Folder Structure

```bash
StudyMate/
├── backend/
│   ├── config/
│   │   └── db.php
│   ├── models/
│   ├── routes/
│   ├── server.php
│   └── .env
├── frontend/
│   ├── student/
│   │   ├── dashboard.php
│   │   ├── availability.php
│   │   ├── session_requests.php
│   │   ├── ratings.php
│   │   └── notifications.php
│   ├── admin/
│   └── assets/
└── README.md
```

---

## ⚙️ Installation Guide

### 1️⃣ Clone the repository

```bash
git clone https://github.com/<your-username>/StudyMate.git
cd StudyMate
```

### 2️⃣ Set up the database

1. Create a database, e.g. `studymate_db`
2. Import the provided SQL schema (`studymate.sql`) via phpMyAdmin

### 3️⃣ Configure database credentials

Edit `/config/db.php`:

```php
$host = "localhost";
$dbname = "";//yourdatabase name
$username = "";//replace with your username
$password = "";//replace with your password
```

### 4️⃣ Run the app

If using XAMPP or WAMP:

* Place folder in `htdocs/`
* Visit:
  👉 `http://localhost/StudyMate/`

---

## 🧩 Key Database Tables

| Table              | Purpose                                  |
| ------------------ | ---------------------------------------- |
| `users`            | Stores user credentials and roles        |
| `availability`     | User time slots for sessions             |
| `session_requests` | Tracks all session requests and statuses |
| `notifications`    | In-app alerts and reminders              |
| `session_feedback` | Ratings and comments                     |
| `subjects`         | List of subjects                         |
| `user_subjects`    | User-subject mappings                    |

---

## 🕓 Automatic Session Completion

Automatically marks sessions as **completed** when the scheduled time passes:

```sql
UPDATE session_requests
SET status = 'completed'
WHERE status = 'accepted'
  AND session_date IS NOT NULL
  AND CONCAT(session_date, ' ', SUBSTRING_INDEX(time_slot, '-', -1)) < NOW();
```

---

## ⭐ Rating & Feedback System

After each completed session:

* Both users are prompted to rate each other (1–5 stars)
* Optional text feedback
* Average rating auto-updates on dashboard
* Peer gets a notification of the new rating

---

## 🧠 Future Enhancements

* 📧 Email & SMS reminders (cron-based)
* 🗓️ Google Calendar integration
* 💬 Real-time chat with WebSockets
* 📊 Analytics dashboard for Admin
* 👥 Group study sessions (multi-user)
* 🤖 AI-based smart peer recommendations

---


## 🤝 Contributing

Contributions are welcome!

---

## 📜 License

This project is licensed under the **MIT License**.

---

## 👨‍💻 Author

**Nandu Panakanti**
📧 Mail: panakantinandu@gmail.com
🌐 Github: https://github.com/panakantinandu
💬 “Study hard, stay consistent, and help others learn — that’s what StudyMate stands for.” 🎯

