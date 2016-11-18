FROM debian:jessie

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -y update
RUN apt-get install -y --force-yes proftpd-basic openssh-server openssl

RUN bash -c 'cat /etc/proftpd/proftpd.conf | sed "s/^.*DefaultRoot.*$/DefaultRoot ~/" | sed "s/^.*ListOptions.*$/ListOptions \"-la\"/" | sed "s/^.*Include\s\+\/etc\/proftpd\/tls.conf\s*$/Include \/etc\/proftpd\/tls.conf/" > /etc/proftpd/proftpd.conf.tmp'
RUN mv /etc/proftpd/proftpd.conf.tmp /etc/proftpd/proftpd.conf

RUN mkdir /etc/proftpd/ssl
RUN openssl req -new -x509 -days 365 -nodes -out /etc/proftpd/ssl/proftpd.cert.pem -keyout /etc/proftpd/ssl/proftpd.key.pem -subj "/C=DE/ST=Berlin/L=Berlin/O=depage.net/OU=IT/CN=depage.net"
RUN chmod 600 /etc/proftpd/ssl/proftpd.*
ADD ssl/tls.conf /etc/proftpd/tls.conf

RUN useradd -m -p co.yjyxRTlonU testuser

ADD ssh/ssh_host_rsa_key /etc/ssh/ssh_host_rsa_key
RUN chmod 600 /etc/ssh/ssh_host_rsa_key
ADD ssh/ssh_host_rsa_key.pub /etc/ssh/ssh_host_rsa_key.pub
RUN mkdir /var/run/sshd

EXPOSE 20 21 22

RUN /etc/init.d/ssh start

CMD proftpd && /usr/sbin/sshd -D
