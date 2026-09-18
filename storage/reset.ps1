<#
.SYNOPSIS
    Script de Reset Total de Desenvolvimento para o LPAF.
.DESCRIPTION
    Limpa storage/data, storage/logs, storage/backups, storage/tmp e modules/,
    preservando storage/.htaccess e todos os arquivos .gitkeep.
    Retorna o sistema para a tela de instalação (/setup).
#>
param(
    [switch]$Force
)

$storageDir = $PSScriptRoot
$rootDir = Split-Path -Parent $storageDir
$modulesDir = Join-Path $rootDir "modules"

Write-Host "====================================================================" -ForegroundColor Cyan
Write-Host "  LPAF - Reset Total de Desenvolvimento (Clean Slate)" -ForegroundColor Cyan
Write-Host "====================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Esta acao ira:" -ForegroundColor Yellow
Write-Host "  - Apagar dados em storage/data/ (mantendo .gitkeep)" -ForegroundColor Yellow
Write-Host "  - Apagar logs em storage/logs/ (mantendo .gitkeep)" -ForegroundColor Yellow
Write-Host "  - Apagar backups em storage/backups/ (mantendo .gitkeep)" -ForegroundColor Yellow
Write-Host "  - Apagar temporarios em storage/tmp/ (mantendo .gitkeep)" -ForegroundColor Yellow
Write-Host "  - Apagar modulos em modules/ (mantendo .gitkeep)" -ForegroundColor Yellow
Write-Host "  - Preservar storage/.htaccess e todos os .gitkeep" -ForegroundColor Yellow
Write-Host ""
Write-Host "O sistema retornara para a tela de instalacao inicial (/setup)." -ForegroundColor Yellow
Write-Host ""

if (-not $Force) {
    $confirm = Read-Host "Deseja realmente resetar o ambiente? (S/N)"
    if ($confirm -notmatch '^[sSyY]') {
        Write-Host "Operacao cancelada pelo usuario." -ForegroundColor Gray
        exit 0
    }
}

Write-Host ""
Write-Host "[1/5] Limpando storage/data..." -ForegroundColor Cyan
Get-ChildItem -Path (Join-Path $storageDir "data") -Exclude ".gitkeep" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "[2/5] Limpando storage/logs..." -ForegroundColor Cyan
Get-ChildItem -Path (Join-Path $storageDir "logs") -Exclude ".gitkeep" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "[3/5] Limpando storage/backups..." -ForegroundColor Cyan
Get-ChildItem -Path (Join-Path $storageDir "backups") -Exclude ".gitkeep" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "[4/5] Limpando storage/tmp..." -ForegroundColor Cyan
Get-ChildItem -Path (Join-Path $storageDir "tmp") -Exclude ".gitkeep" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "[5/5] Limpando modules/..." -ForegroundColor Cyan
Get-ChildItem -Path $modulesDir -Exclude ".gitkeep" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "====================================================================" -ForegroundColor Green
Write-Host "  SUCESSO: Ambiente resetado com exito!" -ForegroundColor Green
Write-Host "  Recarregue a aplicacao no navegador para acessar o /setup." -ForegroundColor Green
Write-Host "====================================================================" -ForegroundColor Green
Write-Host ""
