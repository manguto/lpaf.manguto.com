@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ====================================================================
echo   LPAF - Reset Total de Desenvolvimento (Clean Slate)
echo ====================================================================
echo.
echo Esta acao ira:
echo   - Apagar dados em storage/data/ (mantendo .gitkeep)
echo   - Apagar logs em storage/logs/ (mantendo .gitkeep)
echo   - Apagar backups em storage/backups/ (mantendo .gitkeep)
echo   - Apagar temporarios em storage/tmp/ (mantendo .gitkeep)
echo   - Apagar modulos em modules/ (mantendo .gitkeep)
echo   - Preservar storage/.htaccess e todos os .gitkeep
echo.
echo O sistema retornara para o estado de instalacao inicial (/setup).
echo.

if "%~1"=="-y" goto :do_reset
if "%~1"=="/y" goto :do_reset

set /p CONFIRM="Deseja realmente resetar o ambiente? (S/N): "
if /i not "!CONFIRM!"=="S" (
    echo Operacao cancelada pelo usuario.
    goto :end
)

:do_reset
set "STORAGE_DIR=%~dp0"
set "ROOT_DIR=%STORAGE_DIR%.."
set "MODULES_DIR=%ROOT_DIR%\modules"

echo.
echo [1/5] Limpando storage/data...
for %%F in ("%STORAGE_DIR%data\*") do (
    if not "%%~nxF"==".gitkeep" del /f /q "%%F" >nul 2>&1
)

echo [2/5] Limpando storage/logs...
for %%F in ("%STORAGE_DIR%logs\*") do (
    if not "%%~nxF"==".gitkeep" del /f /q "%%F" >nul 2>&1
)

echo [3/5] Limpando storage/backups...
for /d %%D in ("%STORAGE_DIR%backups\*") do rmdir /s /q "%%D" >nul 2>&1
for %%F in ("%STORAGE_DIR%backups\*") do (
    if not "%%~nxF"==".gitkeep" del /f /q "%%F" >nul 2>&1
)

echo [4/5] Limpando storage/tmp...
for /d %%D in ("%STORAGE_DIR%tmp\*") do rmdir /s /q "%%D" >nul 2>&1
for %%F in ("%STORAGE_DIR%tmp\*") do (
    if not "%%~nxF"==".gitkeep" del /f /q "%%F" >nul 2>&1
)

echo [5/5] Limpando modules/...
for /d %%D in ("%MODULES_DIR%\*") do rmdir /s /q "%%D" >nul 2>&1
for %%F in ("%MODULES_DIR%\*") do (
    if not "%%~nxF"==".gitkeep" del /f /q "%%F" >nul 2>&1
)

echo.
echo ====================================================================
echo   SUCESSO: Ambiente resetado com exito!
echo   Recarregue a aplicacao no navegador para acessar a tela /setup.
echo ====================================================================
echo.

:end
if not "%~1"=="-y" if not "%~1"=="/y" pause
