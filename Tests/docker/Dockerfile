FROM debian:jessie

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -y update
RUN apt-get install -y --force-yes proftpd-basic openssh-server

RUN bash -c 'cat /etc/proftpd/proftpd.conf | sed "s/^.*DefaultRoot.*$/DefaultRoot ~/" | sed "s/^.*ListOptions.*$/ListOptions \"-la\"/" > /etc/proftpd/proftpd.conf.tmp'
RUN mv /etc/proftpd/proftpd.conf.tmp /etc/proftpd/proftpd.conf

RUN useradd -m -p co.yjyxRTlonU testuser

ADD ssh/ssh_host_rsa_key /etc/ssh/ssh_host_rsa_key
RUN chmod 600 /etc/ssh/ssh_host_rsa_key
ADD ssh/ssh_host_rsa_key.pub /etc/ssh/ssh_host_rsa_key.pub
RUN mkdir /var/run/sshd

EXPOSE 20 21 22

RUN /etc/init.d/ssh start

CMD proftpd && /usr/sbin/sshd -D
