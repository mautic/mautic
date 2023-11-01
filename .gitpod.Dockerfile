FROM gitpod/workspace-full
SHELL ["/bin/bash", "-c"]

# Install ddev
RUN brew update && brew install drud/ddev/ddev && mkcert -install
