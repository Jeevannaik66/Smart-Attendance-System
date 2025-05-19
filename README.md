# 📸 Smart Attendance System using Face Recognition

A **full‑stack attendance platform** that combines  
**PHP + MySQL (web dashboard)** with a **Python / Flask + OpenCV (face‑recognition API)**.  
Users are recognised in real‑time, their presence is stored in a MySQL table, and administrators get an interactive web dashboard with charts, filters and data‑export tools.

> **XAMPP on Windows workflow**  
> • **PHP side** → put the *entire* **`frontend`** folder inside `C:\xampp\htdocs\Smart_Attendance_System\` so Apache can serve it.  
> • **Python side** → the **`backend`** folder can live **anywhere outside `htdocs`** (e.g. `D:\Projects\Smart_Attendance_System\backend`).  
>   Just start `app.py`; PHP talks to it over `http://127.0.0.1:5000`.

---

## 📑 Table of Contents

1. [🌍 Project Overview](#-project-overview)  
2. [🧠 Tech Stack](#-tech-stack)  
3. [🚀 Key Features](#-key-features)  
4. [🖼️ System Architecture](#-system-architecture)  
5. [📁 Project Structure](#-project-structure)  
6. [🗂️ Backend API Modules](#-backend-api-modules)  
7. [🧩 Frontend Page Routing](#-frontend-page-routing)  
8. [🔧 Installation & Running the App](#-installation--running-the-app)  
9. [🚦 Usage & Features Explained](#-usage--features-explained)  
10. [📜 License](#-license)

---

## 🌍 Project Overview
The system replaces manual roll‑calls with computer‑vision‑powered attendance.  
An administrator enrols users by uploading a photograph; after that, standing in front of a webcam is enough to mark them **Present**.

---

## 🧠 Tech Stack

| Layer              | Technology                          |
|--------------------|-------------------------------------|
| Web UI             | PHP 7, HTML 5, Bootstrap 5          |
| Face Recognition   | Python 3, Flask, OpenCV 4           |
| Database           | MySQL 8 (via XAMPP)                 |
| Inter‑service API  | REST (JSON, cURL)                   |
| Camera Interface   | Browser `getUserMedia()` + JavaScript|

---

## 🚀 Key Features
- **Automatic Face Recognition** with OpenCV LBPH model  
- **Real‑time camera feed** inside the browser  
- **MySQL attendance log** (date, time, status)  
- **Admin dashboard**: add / edit users, view records, export CSV/PDF  
- **Charts & analytics** for daily / monthly attendance trends  
- **Role‑based access**: admin vs standard user logins

---

## 🖼️ System Architecture
```

PHP Frontend (Apache)  ←→  Flask API (Python)  ←→  OpenCV Model
│                         │
└────── MySQL Database ◄──┘

````
*PHP* handles UI & data‑entry, *Flask* handles computer vision; both read/write the same MySQL instance.

---

## 📁 Project Structure
> **Place the folders exactly like this** to avoid “404” or import errors.

```text
C:\xampp\htdocs\Smart_Attendance_System\         # served by Apache
└─ frontend\
   ├─ api\
   │  ├─ api.php            # calls Flask via cURL
   │  └─ capture.py         # optional local test script
   ├─ assets\css\js\images   # UI resources
   ├─ database\
   │  ├─ db_config.php
   │  └─ smart_attendance_system.sql
   ├─ dataset\              # enrolled face images
   ├─ uploads\              # exported reports
   ├─ admin_login.php
   ├─ admin_register.php
   ├─ dashboard.php
   ├─ manage_users.php
   ├─ live_attendance.php
   ├─ attendance.php
   └─ reports.php

<any_path>\Smart_Attendance_System\backend\      # independent Python service
   ├─ app.py
   ├─ face_recognition.py
   ├─ trained_model.yml
   ├─ haarcascade_frontalface.xml
   └─ requirements.txt
````

---

## 🗂️ Backend API Modules (Flask)

| Endpoint     | Verb | Purpose                                                  |
| ------------ | ---- | -------------------------------------------------------- |
| `/recognise` | POST | Accepts a base64 image, returns `{id, name, confidence}` |
| `/train`     | POST | Rebuilds `trained_model.yml` after new user added        |
| `/ping`      | GET  | Health check for PHP                                     |

---

## 🧩 Frontend Page Routing (PHP)

| Route / File          | Role                            |
| --------------------- | ------------------------------- |
| `admin_login.php`     | Admin authentication            |
| `dashboard.php`       | Quick stats (+link to reports)  |
| `manage_users.php`    | CRUD students / employees       |
| `live_attendance.php` | Real‑time marking via webcam    |
| `attendance.php`      | Tabular log with filters/export |
| `reports.php`         | Charts & PDF / CSV download     |

---

## 🔧 Installation & Running the App

```bash
# 1. Clone repo, then COPY the frontend folder to XAMPP htdocs
mkdir "C:\xampp\htdocs\Smart_Attendance_System"
xcopy /E /I <repo>\frontend  C:\xampp\htdocs\Smart_Attendance_System\frontend

# 2. Import MySQL schema
mysql -u root -p  <  C:\xampp\htdocs\Smart_Attendance_System\frontend\database\smart_attendance_system.sql

# 3. Adjust credentials in db_config.php
notepad C:\xampp\htdocs\Smart_Attendance_System\frontend\database\db_config.php

# 4. Install & start Python backend
cd <repo>\backend
pip install -r requirements.txt
python app.py   # Flask runs on http://127.0.0.1:5000

# 5. Start Apache & MySQL from the XAMPP Control Panel
```

Open a browser at **[http://localhost/Smart\_Attendance\_System/frontend/admin\_login.php](http://localhost/Smart_Attendance_System/frontend/admin_login.php)**.

---

## 🚦 Usage & Features Explained

1. **Login as admin** → default credentials in `admin_register.php`.
2. **Add users** (name, email, photo). Photo is copied into **`dataset/`**.
3. Click **“Retrain Model”** (button calls `/train`) so new faces are recognised.
4. Go to **Live Attendance** → allow camera → recognised faces get auto‑logged.
5. View **Attendance** or **Reports** for filtering, charts, and exports.

---

## 📜 License

MIT © 2025 Jeevan

```
```
