@echo off
REM ================================================
REM Computer Store Database Setup Script
REM MS SQL Server - Windows Authentication
REM Project: I3306F-Project
REM ================================================

SET PROJECT_DIR=C:\I3306F-Project
SET SQLCMD="C:\Program Files\Microsoft SQL Server\Client SDK\ODBC\170\Tools\Binn\sqlcmd.exe"
SET SERVER=localhost

echo ========================================
echo Computer Store Database Setup
echo ========================================
echo.

echo [1/8] Creating database...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createDB.sql" -o "%PROJECT_DIR%\LOG\createDB.log"
echo     Done. Check LOG\createDB.log

echo [2/8] Creating tables...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createTables.sql" -o "%PROJECT_DIR%\LOG\createTables.log"
echo     Done. Check LOG\createTables.log

echo [3/8] Creating indexes...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createIndexes.sql" -o "%PROJECT_DIR%\LOG\createIndexes.log"
echo     Done. Check LOG\createIndexes.log

echo [4/8] Creating triggers...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createTriggers.sql" -o "%PROJECT_DIR%\LOG\createTriggers.log"
echo     Done. Check LOG\createTriggers.log

echo [5/8] Creating views...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createViews.sql" -o "%PROJECT_DIR%\LOG\createViews.log"
echo     Done. Check LOG\createViews.log

echo [6/8] Creating stored procedures...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createProcedures.sql" -o "%PROJECT_DIR%\LOG\createProc.log"
echo     Done. Check LOG\createProc.log

echo [7/8] Creating users...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_createUsers.sql" -o "%PROJECT_DIR%\LOG\createUsers.log"
echo     Done. Check LOG\createUsers.log

echo [8/8] Inserting sample data...
%SQLCMD% -S %SERVER% -E -i "%PROJECT_DIR%\SQL\ComputerStore_insertData.sql" -o "%PROJECT_DIR%\LOG\insertData.log"
echo     Done. Check LOG\insertData.log

echo.
echo ========================================
echo Database setup completed successfully!
echo Check LOG directory for detailed logs.
echo ========================================
pause
