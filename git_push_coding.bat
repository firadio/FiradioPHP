@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
SET path=%path%;C:\data\program\PortableGit\bin\
ECHO =========git push tencent master============
git remote add coding https://e.coding.net/firadio/firadiophp.git
git pull coding master
git push coding master
PAUSE
