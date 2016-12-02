FROM debian:jessie

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -y update
RUN apt-get install -y --force-yes proftpd-basic openssh-server openssl

RUN bash -c 'cat /etc/proftpd/proftpd.conf | sed "s/^.*DefaultRoot.*$/DefaultRoot ~/" | sed "s/^.*ListOptions.*$/ListOptions \"-la\"/" | sed "s/^.*Include\s\+\/etc\/proftpd\/tls.conf\s*$/Include \/etc\/proftpd\/tls.conf/" > /etc/proftpd/proftpd.conf.tmp'
RUN mv /etc/proftpd/proftpd.conf.tmp /etc/proftpd/proftpd.conf

RUN mkdir /etc/proftpd/ssl

# generate ca key & cert
RUN openssl genrsa -out /etc/proftpd/ssl/ca.key 2048
RUN openssl req -x509 -new -key /etc/proftpd/ssl/ca.key -days 3650 -out /etc/proftpd/ssl/ca.pem -subj "/C=DE/ST=Berlin/L=Berlin/O=depage.net-ca/OU=IT/CN=ca.depage.net"

# generate client key & cert
RUN openssl genrsa -out /etc/proftpd/ssl/ftp.key 2048
RUN openssl req -new -key /etc/proftpd/ssl/ftp.key -out /etc/proftpd/ssl/ftp.csr -subj "/C=DE/ST=Berlin/L=Berlin/O=depage.net/OU=IT/CN=localhost"

# generate signed client cert
RUN openssl x509 -req -days 365 -CA /etc/proftpd/ssl/ca.pem -CAkey /etc/proftpd/ssl/ca.key -CAcreateserial -CAserial serial -in /etc/proftpd/ssl/ftp.csr -out /etc/proftpd/ssl/ftp.pem

RUN chmod 600 /etc/proftpd/ssl/*

ADD ssl/tls.conf /etc/proftpd/tls.conf

RUN useradd -m -p co.yjyxRTlonU testuser

ADD ssh/ssh_host_rsa_key /etc/ssh/ssh_host_rsa_key
RUN chmod 600 /etc/ssh/ssh_host_rsa_key
ADD ssh/ssh_host_rsa_key.pub /etc/ssh/ssh_host_rsa_key.pub
RUN mkdir /var/run/sshd

EXPOSE 20 21 22

RUN /etc/init.d/ssh start

CMD proftpd && /usr/sbin/sshd -D

