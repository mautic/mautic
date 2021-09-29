FROM gitpod/workspace-full
SHELL ["/bin/bash", "-c"]

RUN sudo apt-get -qq update
# Install required libraries for Projector + PhpStorm
RUN sudo apt-get -qq install -y python3 python3-pip libxext6 libxrender1 libxtst6 libfreetype6 libxi6

# Install Projector
RUN pip3 install projector-installer
# Install PhpStorm
# Prevents projector install from asking for the license acceptance
RUN mkdir -p ~/.projector/configs
RUN projector install 'PhpStorm 2021.1' --no-auto-run

# Install ddev
RUN brew update && brew install drud/ddev/ddev && mkcert -install
