# SIBAS - Sistem Inventaris Barang dan Supplier

<p align="center" style="background:#222; padding:20px; border-radius:12px;">
  <img src="https://alvinramatech.com/sibas/assets/logo_sibas.png" alt="SIBAS Banner" width="370"/>
</p>

SIBAS (Sistem Inventaris Barang dan Supplier) is a web-based inventory and supplier management system built with **PHP** and **MySQL**. This project was developed as a final assignment for the Web Programming course.

## 🚀 Live Demo

Access the live demo here: [https://alvinramatech.com/sibas](https://alvinramatech.com/sibas)

---

## 📚 Features

- **Inventory Management**: Add, edit, and delete inventory items with stock tracking.
- **Supplier Management**: Manage supplier data and history.
- **User Authentication**: Secure login/logout functionality for users.
- **Transaction Records**: Track incoming and outgoing goods.
- **Responsive UI**: Works well across desktops and mobile devices.
- **Reporting**: Generate inventory and supplier reports.

## 🛠️ Built With

- **Backend**: PHP (Native)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Bootstrap)
- **Other**: DataTables, Chart.js

## 📦 Installation

1. **Clone this repository:**
   ```bash
   git clone https://github.com/Alvinnn-R/sibas-webapp.git
   ```
2. **Import the Database:**

   - Find the database SQL file (e.g. `db_sibas.sql`) in the `/database` folder.
   - Import it into your MySQL server using phpMyAdmin or the MySQL CLI.

3. **Configure the Database Connection:**
   - Edit the database settings in `app/config.php` or the relevant config file:
     ```php
     $host = 'localhost';
     $user = 'your_mysql_user';
     $pass = 'your_mysql_password';
     $db   = 'db_sibas';
     ```
4. **Run the Application:**
   - Place the project folder in your web server's directory (e.g. `htdocs` or `www`).
   - Access via browser: `http://localhost/sibas-webapp`

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 📞 Contact & Support

For questions or support, please open an issue or contact the maintainer via [GitHub Issues](https://github.com/Alvinnn-R/sibas-webapp/issues).

---

> **SIBAS** - Sistem Inventaris Barang dan Supplier, Web Programming Final Project.
