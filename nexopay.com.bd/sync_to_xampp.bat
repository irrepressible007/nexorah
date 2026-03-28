@echo off
set DEST=C:\xampp\htdocs\nexorah\nexopay

echo Syncing files to %DEST%...

copy /Y "admin\db_migrate.php" "%DEST%\admin\db_migrate.php"
copy /Y "admin\db_fix.php" "%DEST%\admin\db_fix.php"
copy /Y "admin\registrations.php" "%DEST%\admin\registrations.php"
copy /Y "admin\dashboard.php" "%DEST%\admin\dashboard.php"
copy /Y "admin\ca_applications.php" "%DEST%\admin\ca_applications.php"
copy /Y "admin\volunteer_applications.php" "%DEST%\admin\volunteer_applications.php"
copy /Y "admin\includes\sidebar.php" "%DEST%\admin\includes\sidebar.php"
copy /Y "register-ca.php" "%DEST%\register-ca.php"
copy /Y "register-volunteer.php" "%DEST%\register-volunteer.php"
copy /Y "register.php" "%DEST%\register.php"
copy /Y "success.php" "%DEST%\success.php"
copy /Y "index.php" "%DEST%\index.php"
copy /Y "events.php" "%DEST%\events.php"
copy /Y "about.php" "%DEST%\about.php"
copy /Y "contact.php" "%DEST%\contact.php"
copy /Y "user-panel.php" "%DEST%\user-panel.php"
copy /Y "event-details.php" "%DEST%\event-details.php"
copy /Y "config\nexorah_db.php" "%DEST%\config\nexorah_db.php"

echo Copied successfully.
