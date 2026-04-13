# QuickPOS — Web-Based Point of Sale System
> Built with PHP + MySQL + Bootstrap 5 | Runs on XAMPP/WAMP

---

## 📁 Folder Structure

```
pos-system/
├── index.php               ← Root redirect to login
├── setup.php               ← One-time setup (delete after use!)
├── database.sql            ← Full DB schema + sample data
├── config/
│   └── db.php              ← Database connection
├── includes/
│   ├── header.php          ← Shared navbar/HTML head
│   ├── footer.php          ← Closing HTML tags
│   └── auth_guard.php      ← Session protection
├── auth/
│   ├── login.php           ← Login form + auth logic
│   └── logout.php          ← Session destroy + redirect
├── products/
│   ├── index.php           ← Product list (Admin)
│   ├── add.php             ← Add product (Admin)
│   ├── edit.php            ← Edit product (Admin)
│   └── delete.php          ← Delete product (Admin)
├── pos/
│   ├── pos.php             ← Main POS interface (Cashier + Admin)
│   └── checkout.php        ← AJAX checkout handler
├── reports/
│   ├── index.php           ← Sales reports (Admin)
│   └── sale_detail.php     ← Individual sale receipt (Admin)
└── assets/
    ├── css/
    │   └── style.css       ← Global styles
    └── js/
        └── pos.js          ← Cart logic, checkout, receipts
```

---

## 🚀 Deployment Steps

### Step 1 — Copy Files
Place the entire `pos-system/` folder inside:
- **XAMPP**: `C:\xampp\htdocs\pos-system\`
- **WAMP**: `C:\wamp64\www\pos-system\`

### Step 2 — Create the Database
1. Open your browser: `http://localhost/phpmyadmin`
2. Click **New** → name it `pos_db` → click **Create**
3. Select `pos_db` → click the **SQL** tab
4. Open `database.sql`, copy all content, paste it → click **Go**

### Step 3 — Run Setup Script
Visit: **`http://localhost/pos-system/setup.php`**

This creates the default admin and cashier accounts with correctly hashed passwords.

> ⚠️ **Delete `setup.php` after this step!**

### Step 4 — Launch the App
Visit: **`http://localhost/pos-system/`**

You'll be redirected to the login page.

---

## 👤 Default Accounts

| Username  | Password    | Role    | Access                        |
|-----------|-------------|---------|-------------------------------|
| `admin`   | `admin123`  | Admin   | Full access (all modules)     |
| `cashier` | `cashier123`| Cashier | POS interface only            |

---

## ✅ Feature Checklist

| Feature                          | Status |
|----------------------------------|--------|
| Login / Logout                   | ✅     |
| Role-based access (Admin/Cashier)| ✅     |
| Product CRUD (Add/Edit/Delete)   | ✅     |
| Product search                   | ✅     |
| Low stock badge                  | ✅     |
| POS cart (add/remove/qty)        | ✅     |
| Real-time total calculation      | ✅     |
| Checkout + change calculation    | ✅     |
| Receipt generation               | ✅     |
| Print receipt                    | ✅     |
| Inventory auto-deduction         | ✅     |
| Sales recording in DB            | ✅     |
| Daily revenue report             | ✅     |
| Transaction history              | ✅     |
| Low stock alerts                 | ✅     |
| Top selling products             | ✅     |
| Date range filtering             | ✅     |
| SQL injection prevention         | ✅     |
| Password hashing                 | ✅     |
| Input validation                 | ✅     |

---

## 🛡️ Security Notes

- All DB queries use **prepared statements** (MySQLi)
- Passwords use PHP `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- Sessions protect all pages — unauthenticated users are redirected
- Admin-only pages have a role check (`$adminOnly = true`)
- HTML output uses `htmlspecialchars()` to prevent XSS

---

## 🔧 Customization

**Change currency symbol**: Search for `GH₵` in all PHP/JS files and replace with your currency.

**Change DB credentials**: Edit `config/db.php` — update `DB_USER` and `DB_PASS`.

**Add more users**: Use phpMyAdmin → insert into `users` table with `password_hash('yourpassword', PASSWORD_DEFAULT)`.

---

*Built by Asorbono Dominic | QuickPOS v1.0*
