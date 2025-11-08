# server-side-project

## Folder Stucture
server-side-project/
├─ config/
│  ├─ app.php            # App settings (env, base URL, timezone)
│  └─ database.php       # DB credentials
├─ src/
│  ├─ bootstrap.php      # Session, timezone, error mode, autoload-ish includes
│  ├─ db.php             # PDO connection (singleton)
│  ├─ functions.php      # Small helpers (csrf, redirect, sanitize)
│  └─ Auth.php           # All auth-related queries (register, login, fetch user)
├─ public/
│  ├─ index.php          # Landing page (simple)
│  ├─ register.php       # Registration screen + handler
│  ├─ login.php          # Login screen + handler
│  └─ logout.php         # Destroy session
└─ .htaccess             # Route /public as web root (optional if using Apache)

