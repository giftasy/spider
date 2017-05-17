@echo off

echo 请输入配置文件名称：
set /p configFile=
echo 您选择的是:%configFile%
echo 确定执行么？
@pause

php crawlers.php %configFile%

echo 恭喜你，%configFile%执行成功！
@pause
exit