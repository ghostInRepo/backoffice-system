Rotate health token scheduler (Windows Task Scheduler)

You can automatically rotate the health token using Windows Task Scheduler. Two options are provided:

1. Run PHP script directly

- Program/script:
  C:\xampp\php\php.exe
- Add arguments:
  "C:\xampp\htdocs\backoffice_system\scripts\rotate_health_token.php"
- Start in:
  C:\xampp\htdocs\backoffice_system\scripts

2. Use the bundled batch wrapper

- Program/script:
  C:\xampp\htdocs\backoffice_system\scripts\rotate_health_token.bat
- Add arguments: (optional) --ttl=3600 --quiet
- Start in:
  C:\xampp\htdocs\backoffice_system\scripts

Example schtasks command (run as Administrator) to schedule daily rotation at 03:00:

schtasks /Create /SC DAILY /TN "Backoffice\RotateHealthToken" /TR "C:\\xampp\\php\\php.exe C:\\xampp\\htdocs\\backoffice_system\\scripts\\rotate_health_token.php --quiet" /ST 03:00 /F

Notes:

- The CLI supports --ttl=SECONDS to override token lifetime for this rotation.
- The helper stores the token in var/health_token.json and logs rotations in var/logs/health_rotation.log.
- If you prefer rotation to run more frequently, adjust the Task Scheduler schedule accordingly.
