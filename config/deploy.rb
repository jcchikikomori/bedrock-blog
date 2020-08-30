set :application, 'jccorsanes.site'
set :repo_url, 'git@github.com:jcchikikomori/bedrock-blog.git'

set :branch, :master

set :deploy_to, '/var/www/html'

set :log_level, :info
set :pty, true

set :linked_dirs, %w{media web/app}
set :linked_files, %w{.env web/.user.ini}

set :theme_path, "#{release_path}/app/themes/jcc-blog-2020-theme"

set :npm_target_path, fetch(:theme_path)
set :grunt_target_path, fetch(:theme_path)

namespace :deploy do

  desc 'Restart application'
  task :restart do
    on roles(:app) do
      sudo 'service', 'nginx', 'reload'
    end
  end

end

before 'deploy:updated', 'grunt'