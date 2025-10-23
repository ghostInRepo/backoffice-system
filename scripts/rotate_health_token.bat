@echo off
REM Wrapper to run rotate_health_token.php from Task Scheduler
SET PHP_EXE=C:\xampp\php\php.exe
IF NOT EXIST "%PHP_EXE%" (
  echo PHP not found at %PHP_EXE%. Please update the path in this batch file.
  exit /b 1
)
"%PHP_EXE%" "%~dp0rotate_health_token.php" %*
