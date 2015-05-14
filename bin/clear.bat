set this=%CD%
set root=%this%/..

cd /d "%root%/temp"
del "btfj.dat"
rd /s /q cache
rd /s /q proxies
rd /s /q install
cd /d "%this%"

cd /d "%root%/www/webtemp"
del *.css
del *.js
cd /d "%this%"

cd /d "%root%/tests"
del "test.log"
cd /d "%this%"

cd /d "%root%/tests/tmp"
del "btfj.dat"
del lock*
rd /s /q cache
rd /s /q proxies
rd /s /q install
cd /d "%this%"

exit