@echo off

SET PORT=8891

echo �ر� bocom pay2socket ��������
netstat -ano | findstr %PORT% | findstr LISTENING > temp.txt
for /f "tokens=5" %%i in (temp.txt) do  taskkill /f /pid %%i /t
del /f temp.txt
pause