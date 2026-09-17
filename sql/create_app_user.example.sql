-- 관리자 권한이 있는 MariaDB 클라이언트에서 실행하세요.
-- 아래 두 곳의 CHANGE_THIS_PASSWORD를 실제로 사용할 강한 비밀번호로 바꾸세요.

CREATE USER IF NOT EXISTS 'eum_app'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

ALTER USER 'eum_app'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

GRANT SELECT, INSERT, UPDATE ON eumsolution.* TO 'eum_app'@'127.0.0.1';
FLUSH PRIVILEGES;
