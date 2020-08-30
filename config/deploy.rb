set :application, 'jccorsanes.site'
set :repo_url, 'git@github.com:jcchikikomori/bedrock-blog.git'

set :branch, :master

set :deploy_to, '/var/www/html'

set :log_level, :info
set :pty, true

set :linked_dirs, %w{media web/app/updraft web/app/uploads}
set :linked_files, %w{.env web/.user.ini web/app/advanced-cache.php}

set :themes_path, "#{release_path}/web/app/themes/"
set :plugins_path, "#{release_path}/web/app/plugins"
set :muplugins_path, "#{release_path}/web/app/mu-plugins"

# set :npm_target_path, fetch(:theme_path)
# set :grunt_target_path, fetch(:theme_path)

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

  desc 'Build needed directories'
  task :build_dirs do
    on roles(:app) do
      execute "mkdir -p #{fetch(:themes_path)}"
      execute "mkdir -p #{fetch(:plugins_path)}"
      execute "mkdir -p #{fetch(:muplugins_path)}"
    end
  end

  desc 'Build theme'
  task :build_theme do
    on roles(:app) do
      execute "cd #{fetch(:themes_path)}/jcc-blog-2020-theme && npm install && npm run prod"
    end
  end

  desc 'Fix directory permissions'
  task :chown_dirs do
    on roles(:app) do
      execute "sudo chown -R www-data:www-data #{fetch(:themes_path)}"
      execute "sudo chown -R www-data:www-data #{fetch(:plugins_path)}"
      execute "sudo chown -R www-data:www-data #{fetch(:muplugins_path)}"
    end
  end

end

# custom setups
before 'composer:run', 'deploy:build_dirs'
after 'deploy', 'deploy:build_theme'
after 'deploy:build_theme', 'deploy:chown_dirs'
after 'deploy:chown_dirs', 'deploy:restart'