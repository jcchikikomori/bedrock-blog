set :stage, :staging

server 'jccorsanes.dev', user: 'vagrant', roles: %w{web app db}

# Vagrant specific
set :ssh_options, {
  keys: %w(~/.vagrant.d/insecure_private_key)
}