@echo off
cd /d "c:\Users\bouch\OneDrive\Desktop\sport_insight-main"

:: Generate CRUD for Product
(
    echo Product
    echo.
    echo n
) | php bin/console make:crud

echo CRUD generation complete.
