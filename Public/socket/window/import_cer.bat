@echo off

SET JAVA_HOME=C:\Program Files\Java\IBMJDK5.0\jre

ECHO Y|"%JAVA_HOME%\bin\keytool" -import -keystore "%JAVA_HOME%\lib\security\cacerts" -storepass changeit -alias test_bocommca6 -file "./cert/test_root.cer"
pause