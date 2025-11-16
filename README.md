---

# 🎓 StudyMate – Peer Learning & Scheduling Platform

A complete **peer learning WebApp** built using **PHP, MySQL, Bootstrap, jQuery**, providing students and tutors an organized way to schedule sessions, exchange feedback, manage availability, and collaborate effectively.

---

## 🧭 System Overview

```
Users → Set Subjects → Set Availability
      ↓
Send/Receive Session Requests
      ↓
Accepted Request → Auto Meeting Link (Jitsi)
      ↓
Session Auto-Completed After End Time
      ↓
Both Users Give Ratings + Feedback
      ↓
Admin Monitors Everything (Users, Sessions, Reports)
```

---

## 🚀 Features

### 👩‍🎓 Student / User

* Set weekly availability
* Match with peers or tutors
* Send/accept/reject session requests
* Auto-generated meeting link
* Auto-completion of past sessions
* Notifications + reminders
* Ratings & feedback for tutors
* Dashboard analytics (sessions, completed, rating, unread)

### 🧑‍🏫 Tutor

* Manage sessions with students
* Accept or reject requests
* Join live sessions
* Receive ratings and comments

### 🧑‍💼 Admin

* Manage users, subjects, and sessions
* View analytics and feedback
* Full system monitoring

---

## 📁 Project Structure

```
StudyMate/
├── assets/
│   ├── css/
│   ├── images/
│   └── js/
│       └── validation.js
├── auth/
│   ├── login_admin.php
│   ├── login_student.php
│   ├── logout.php
│   └── register.php
├── config/
│   └── pdo.php              <-- (ignored in Git)
├── functions/
│   ├── email_helper.php
│   ├── notification.php
│   ├── utils.php
│   └── validation.php
├── includes/
│   ├── auth.php
│   ├── navbar_admin.php
│   ├── session_check_admin.php
│   ├── session_check_student.php
│   └── session_check_teacher.php
├── src/
│   └── PHPMailer/           <-- Local mailer library
├── uploads/
│   └── profile_images/
├── views/
│   ├── admin/
│   ├── student/
│   └── teacher/
├── index.php
├── logout1.php
└── README.md
```

---

## ⚙️ Installation

### 1️⃣ Clone the Repository

```sh
git clone https://github.com/<your-username>/StudyMate.git
cd StudyMate
```

### 2️⃣ Create the Database

1. Open phpMyAdmin
2. Create a database: `studymate_db`
3. Import `studymate.sql`

### 3️⃣ Configure Database Connection

Edit `config/pdo.php` (not pushed to Git):

```php
$dsn      = "mysql:host=localhost;dbname=studymate_db;charset=utf8mb4";
$username = "your_mysql_username";
$password = "your_mysql_password";

$pdo = new PDO($dsn, $username, $password, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
```

### 4️⃣ Run the App

Place the project in your web server folder:

* **XAMPP** → `htdocs/StudyMate/`
* Visit:

```
http://localhost/StudyMate/
```

---

## 🗄️ Main Database Tables

| Table              | Description                                |
| ------------------ | ------------------------------------------ |
| `users`            | User credentials and roles (admin/student) |
| `subjects`         | List of subjects                           |
| `user_subjects`    | User–subject mapping                       |
| `availability`     | Weekly time slots                          |
| `session_requests` | All session requests + statuses            |
| `notifications`    | Alerts for users                           |
| `session_feedback` | Ratings + comments                         |

---

## ⭐ Ratings & Feedback

After every completed session:

* Both users rate each other (1–5 stars)
* Optional comment
* Dashboard auto-updates the average rating
* User receives a new notification

---

## 🔄 Auto Session Completion

Sessions automatically move to **completed** when end time passes:

```sql
UPDATE session_requests
SET status = 'completed'
WHERE status = 'accepted'
  AND STR_TO_DATE(
        CONCAT(session_date, ' ', SUBSTRING_INDEX(time_slot, '-', -1)),
        '%Y-%m-%d %h:%i %p'
      ) < NOW();
```

---

## 📬 Email Support (PHPMailer)

Located in `functions/email_helper.php`:

```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'your_email@gmail.com';
$mail->Password = 'your_app_password';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
```

Used for:

* Sending OTP for password reset
* Account notifications

---

## 🧠 Future Improvements

* SMS + email reminders (cron)
* Google Calendar sync
* Real-time chat
* Group study sessions
* AI-based peer recommendations

---

## 📝 License

MIT License.

---

## 👨‍💻 Author

**Nandu Panakanti**

---

✅ A **professional README table of contents**
Just say the word.
