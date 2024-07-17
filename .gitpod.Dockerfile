FROM gitpod/workspace-full
SHELL ["/bin/bash", "-c"]

# Add DDEV’s GPG key to your keyring
RUN curl -fsSL https://pkg.ddev.com/apt/gpg.key | gpg --dearmor | sudo tee /etc/apt/trusted.gpg.d/ddev.gpg > /dev/null

# Add DDEV releases to your package repository
RUN echo "deb [signed-by=/etc/apt/trusted.gpg.d/ddev.gpg] https://pkg.ddev.com/apt/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list >/dev/null

# Update package information and install DDEV
<<<<<<< HEAD
<<<<<<< HEAD
RUN sudo apt update && sudo apt install -y ddev=1.23.3
=======
RUN sudo apt update && sudo apt install -y ddev=1.22.7
>>>>>>> 1507aeb51d (Fixed gitpod installation for Mautic 5.0.)
=======
RUN sudo apt update && sudo apt install -y ddev=1.22.7
>>>>>>> dc6114e482af3f47699520f271d6080d6c0529f4
