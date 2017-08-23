#!/bin/ksh
umask 000

echo "关闭 bocom pay2socket 监听服务"
for pid in `ps -ef|grep java|grep pay2socket|awk '{print $2}'`
do
                kill -9 $pid 1>/dev/null 2>/dev/null
done