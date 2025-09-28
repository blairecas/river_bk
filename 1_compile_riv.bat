@echo off

set NAME=river

echo.
echo ===========================================================================
echo Compiling CPU
echo ===========================================================================
php -f ../scripts/preprocess.php %NAME%.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\scripts\macro11 -ysl 32 -yus -l _%NAME%.lst _%NAME%.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../scripts/lst2bin.php _%NAME%.lst ./release/%NAME%.bin bbk
if %ERRORLEVEL% NEQ 0 ( exit /b )

del _%NAME%.mac
rem del _%NAME%.lst

echo.
start ..\..\bkemu\BK_x64.exe /C BK-0011M /B .\release\%NAME%.bin