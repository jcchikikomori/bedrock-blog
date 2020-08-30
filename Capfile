# Load DSL and Setup Up Stages
require 'capistrano/setup'

# Includes default deployment tasks
require 'capistrano/deploy'

# Custom tasks
require 'capistrano/composer'
require 'capistrano/file-permissions'
require 'capistrano/nvm'
# require 'capistrano/grunt'

# Loads custom tasks from `lib/capistrano/tasks' if you have any defined.
# Dir.glob('lib/capistrano/tasks/*.cap').each { |r| import r }

# Permissions
set :file_permissions_paths, ["web", "vendor", "config"]
set :file_permissions_users, ["www-data"]

# Composer
set :composer_install_flags, '--no-dev --no-interaction --quiet --optimize-autoloader'
set :composer_roles, :all
set :composer_working_dir, -> { fetch(:release_path) }
set :composer_dump_autoload_flags, '--optimize'

# NVM
set :nvm_type, :user # or :system, depends on your nvm setup
set :nvm_node, 'v11.15.0'
set :nvm_map_bins, %w{node npm node-sass}