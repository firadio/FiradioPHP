@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
ECHO =========git push tencent master============
git remote add tencent https://gitee.com/firadio/firadiophp.git
git pull tencent master
git push tencent master
PAUSE