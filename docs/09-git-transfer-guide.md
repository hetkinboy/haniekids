# Huong dan luu project len Git va mo tren may khac

## 1. Khong commit thu muc phu thuoc

Khong dua cac thu muc/file sau len Git:

```text
frontend/node_modules/
backend/vendor/
frontend/dist/
frontend/.angular/
backend/writable/cache/
backend/writable/debugbar/
backend/writable/logs/
backend/writable/session/
backend/.env
.env
*.log
```

Neu chua co `.gitignore` o thu muc goc, tao file `.gitignore` voi noi dung:

```gitignore
frontend/node_modules/
backend/vendor/
frontend/dist/
frontend/.angular/
frontend/.cache/

backend/writable/cache/
backend/writable/debugbar/
backend/writable/logs/
backend/writable/session/
backend/writable/uploads/

backend/.env
frontend/.env
.env

.DS_Store
Thumbs.db
.vscode/
.idea/

*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*
```

## 2. Commit project

Chay tai thu muc goc:

```powershell
cd D:\tiktok-shop-cost-planning
git init
git add .gitignore
git add .
git status
git commit -m "Save TikTok shop cost manager project"
```

Neu da lo add `node_modules` hoac `vendor`:

```powershell
git rm -r --cached frontend/node_modules backend/vendor
git add .gitignore
git commit -m "Remove generated dependencies from git"
```

## 3. Day len GitHub/GitLab

Tao repository rong tren GitHub/GitLab, sau do chay:

```powershell
git remote add origin https://github.com/USERNAME/REPO.git
git branch -M main
git push -u origin main
```

Neu remote da ton tai:

```powershell
git remote set-url origin https://github.com/USERNAME/REPO.git
git push -u origin main
```

## 4. Backup database

Code khong bao gom du lieu database. Can export database rieng.

MySQL:

```powershell
mysqldump -u root -p ten_database > backup.sql
```

Luu `backup.sql` rieng qua USB/cloud rieng. Khong nen commit file backup neu co token, thong tin shop, don hang that.

## 5. Mo project tren may khac

Clone code:

```powershell
git clone https://github.com/USERNAME/REPO.git
cd REPO
```

Cai backend:

```powershell
cd backend
composer install
copy env .env
```

Sua `backend/.env` cho dung database may moi, sau do import database:

```powershell
mysql -u root -p ten_database < backup.sql
php spark migrate
```

Cai frontend:

```powershell
cd ..\frontend
npm install
```

Chay backend:

```powershell
cd ..\backend
php spark serve --host 127.0.0.1 --port 8080
```

Chay frontend o terminal khac:

```powershell
cd D:\path\to\REPO\frontend
npm start
```

Mo:

```text
http://127.0.0.1:4200
```

## 6. Cap nhat tiep vao Git

Sau khi sua code:

```powershell
git status
git add .
git commit -m "Describe the change"
git push
```

Truoc khi lam tiep tren may khac:

```powershell
git pull
```

