@ECHO OFF
SET BIN_TARGET=%~dp0../vendor/dg/ftp-deployment/Deployment/deployment.php
php "%BIN_TARGET%" %~dp0/deployment_test.php%*
