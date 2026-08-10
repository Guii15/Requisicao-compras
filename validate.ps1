Write-Host "Validando projeto antes do deploy..." -ForegroundColor Yellow

# 1. Testes
Write-Host "`nRodando testes..." -ForegroundColor Cyan
php artisan test
if ($LASTEXITCODE -ne 0) {
    Write-Host "Testes falharam! Corrija antes de fazer push." -ForegroundColor Red
    exit 1
}

Write-Host "`nTUDO VALIDADO - pode fazer push!" -ForegroundColor Green
