@echo off
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (
  echo PHP nao foi encontrado no PATH.
  pause
  exit /b 1
)
if not exist "index.php" (
  echo ERRO: index.php nao foi encontrado nesta pasta.
  echo Pasta atual: %CD%
  pause
  exit /b 1
)
echo.
echo WWAnalog
echo Servidor: http://127.0.0.1:8000
echo Pasta: %CD%
echo.
php -S 127.0.0.1:8000 -t "%CD%"
pause
