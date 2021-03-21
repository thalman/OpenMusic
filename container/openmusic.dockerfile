FROM drupal:8

ENV QT_QPA_PLATFORM=offscreen

# enable backports to get musescore 3
COPY backports.list /etc/apt/sources.list.d/

# add mscz wrapper
COPY mscore-extract /usr/bin/

# install musescore 3 and python 3
RUN apt-get update && apt-get install -y \
  musescore3 python3 && \
  rm -rf /var/lib/apt/lists/* && \
  mkdir /opt/mscore-home && \
  chown 33:33 /opt/mscore-home
