FROM docker.elastic.co/beats/filebeat:7.16.2
USER root

COPY --chown=root:filebeat docker/filebeat/filebeat.prod.yml /usr/share/filebeat/filebeat.yml

RUN chmod 755 /usr/share/filebeat/filebeat.yml
