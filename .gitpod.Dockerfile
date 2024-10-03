FROM gitpod/workspace-full
SHELL ["/bin/bash", "-c"]

RUN curl -fsSL https://pkg.ddev.com/apt/gpg.key | gpg --dearmor | tee /etc/apt/keyrings/ddev.gpg > /dev/null
RUN chmod a+r /etc/apt/keyrings/ddev.gpg

RUN echo "deb [signed-by=/etc/apt/keyrings/ddev.gpg] https://pkg.ddev.com/apt/ * *" | tee /etc/apt/sources.list.d/ddev.list >/dev/null

# Update package information and install DDEV
RUN apt update && apt install -y ddev
