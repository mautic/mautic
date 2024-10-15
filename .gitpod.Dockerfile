FROM gitpod/workspace-full
SHELL ["/bin/bash", "-c"]

RUN sudo apt-get update && sudo apt-get install -y curl
RUN sudo install -m 0755 -d /etc/apt/keyrings
RUN curl -fsSL https://pkg.ddev.com/apt/gpg.key | gpg --dearmor | sudo tee /etc/apt/keyrings/ddev.gpg > /dev/null
RUN sudo chmod a+r /etc/apt/keyrings/ddev.gpg

RUN echo "deb [signed-by=/etc/apt/keyrings/ddev.gpg] https://pkg.ddev.com/apt/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list >/dev/null

# Update package information and install DDEV
RUN sudo apt-get update && sudo apt-get install -y ddev
