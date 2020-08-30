set :stage, :production

server 'jccorsanes.site', user: 'john', roles: %w{web app db}

set :ssh_options, {
  forward_agent: true
}