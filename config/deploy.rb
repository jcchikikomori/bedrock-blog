set :application, 'jccorsanes.site'
set :repo_url, 'git@github.com:jcchikikomori/bedrock-blog.git'

set :branch, :master

set :deploy_to, '/var/www/html'

set :log_level, :info
set :pty, true

set :linked_dirs, %w{media web/app/cache web/app/mu-plugins web/app/nfwlog web/app/themes web/app/updraft web/app/uploads web/app/w3tc-config}
set :linked_files, %w{.env web/.user.ini web/app/advanced-cache.php}

set :theme_path, "#{release_path}/web/app/themes/jcc-blog-2020-theme"

set :npm_target_path, fetch(:theme_path)
set :grunt_target_path, fetch(:theme_path)

namespace :deploy do

  desc 'List themes'
  task :list_themes do
    on roles(:app) do
      execute "ls -l #{release_path}/web/app/themes"
    end
  end

  desc 'Restart application'
  task :restart do
    on roles(:app) do
      sudo 'service', 'nginx', 'reload'
    end
  end

  desc 'Build theme'
  task :build_theme do
    on roles(:app) do
      execute "cd #{fetch(:theme_path)} && npm install && npm run prod"
    end
  end

end

# before 'deploy:updated', 'grunt'
after 'deploy', 'deploy:build_theme'
after 'deploy:build_theme', 'deploy:restart'