@echo off

echo �����������ļ����ƣ�
set /p configFile=
echo ��ѡ�����:%configFile%
echo ȷ��ִ��ô��
@pause

php crawlers.php %configFile%

echo ��ϲ�㣬%configFile%ִ�гɹ���
@pause
exit