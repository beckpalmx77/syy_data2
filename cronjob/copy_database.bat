@echo off
set source=D:\BplusDB-BAKUP
set destination=\\192.168.39.10\Back Bplus new\Bplus payroll

xcopy %source%\* %destination% /E /H /C /I /Y

echo Files copied from %source% to %destination%
//del /f file.txt