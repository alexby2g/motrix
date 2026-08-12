@echo off
setlocal
cd /d "%~dp0"

echo ==========================================
echo   MOTRIX - Publicar codigo limpio a GitHub
echo ==========================================
echo.

where git >nul 2>&1
if errorlevel 1 (
  echo ERROR: Git no esta instalado o no esta en PATH.
  echo Instala Git for Windows y vuelve a ejecutar este archivo.
  pause
  exit /b 1
)

if not exist .git (
  git init
)

git branch -M main

git remote get-url origin >nul 2>&1
if errorlevel 1 (
  git remote add origin https://github.com/alexby2g/motrix.git
) else (
  git remote set-url origin https://github.com/alexby2g/motrix.git
)

git config user.name "alexby2g"
git config user.email "287088731+alexby2g@users.noreply.github.com"

echo.
echo [1/4] Leyendo el estado actual de GitHub...
git fetch origin main
if errorlevel 1 goto :error

echo [2/4] Conservando el historial remoto y preparando Motrix...
git reset origin/main
if errorlevel 1 goto :error

echo [3/4] Creando commit con backend, frontend y despliegue...
git add -A
git commit -m "Preparar Motrix para Neon Render y Vercel"
if errorlevel 1 (
  git diff --cached --quiet
  if not errorlevel 1 (
    echo No hay cambios nuevos para publicar.
  ) else (
    goto :error
  )
)

echo [4/4] Subiendo Motrix a GitHub...
git push -u origin main
if errorlevel 1 goto :error

echo.
echo ==========================================
echo   LISTO: Motrix fue publicado en GitHub.
echo ==========================================
echo Repositorio: https://github.com/alexby2g/motrix
pause
exit /b 0

:error
echo.
echo ERROR: Git no pudo completar la publicacion.
echo Si GitHub solicita inicio de sesion, completalo y vuelve a ejecutar el archivo.
pause
exit /b 1
