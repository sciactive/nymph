FROM mysql:8.0

RUN echo "[mysqld]" > /etc/mysql/conf.d/auth.cnf
RUN echo "default_authentication_plugin=mysql_native_password" >> /etc/mysql/conf.d/auth.cnf
