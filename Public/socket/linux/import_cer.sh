#!/bin/ksh
JAVA_HOME=/usr/java6_64/jre

echo Y|"$JAVA_HOME/bin/keytool" -import -keystore "$JAVA_HOME/lib/security/cacerts" -storepass changeit -alias test_bocommca6 -file "./cert/test_root.cer"
