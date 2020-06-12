<?php

namespace Deployer;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/application.php';

require 'recipe/common.php';
require 'recipe/cloudflare.php';
require 'vendor/jcchikikomori/deployer-wp-recipes/recipes/assets.php';
require 'vendor/jcchikikomori/deployer-wp-recipes/recipes/cleanup.php';
require 'vendor/jcchikikomori/deployer-wp-recipes/recipes/db.php';
require 'vendor/jcchikikomori/deployer-wp-recipes/recipes/uploads.php';

/**
 * Server Configuration
 */

// Dotenv
before('db:cmd:pull', 'env:uri');
before('db:cmd:push', 'env:uri');

// Define servers
inventory('servers.yml');

// Default server
set('application', 'production-blog');
set('default_stage', 'production');

// Temporary directory path
set('tmp_path', '/tmp/deployer');

// Theme name under themes dir
// set('theme_name_full', 'web/app/themes/{{theme_name}}');

// Important: set directory with slash "/"
set('wp-recipes', [
    'theme_name'        => '{{theme_name}}',
    'theme_dir'         => 'web/app/themes/',
    'shared_dir'        => '{{deploy_path}}/shared/',
    'assets_dist'       => 'web/app/themes/{{theme_name}}/',
    'theme_dist'       => '{{theme_name}}', // found bug
    // Leave it empty, because we're gonna setup on .env file
    'local_wp_url'      => '',
    'remote_wp_url'     => '',
    'clean_after_deploy'=>  [
        'deploy.php',
        '.gitignore',
        '*.md'
    ]
]);

/**
 * Bedrock Configuration
 */

// Bedrock project repository
set('repository', 'git@bitbucket.org:gorated/bedrock-chatgenie.git');

// Bedrock shared files
// Shared files/dirs between deploys
// NOTE: Don't add web/.htninja here!
set('shared_files', [
    '.env', 'web/.htaccess'
]);

// Bedrock shared directories
// Retain old directory, in case of
set('shared_dirs', [
    'web/app/uploads',
    'web/app/w3tc-config',
    'web/app/nfwlog',
    'web/app/cache',
    'wp-content/uploads'
]);

// Bedrock writable directories
// Writable dirs by web server
// Retain old directory, in case of
set('writable_dirs', [
    'web/app/uploads',
    'web/app/w3tc-config',
    'web/app/nfwlog',
    'web/app/cache',
    'wp-content/uploads',
]);
set('allow_anonymous_stats', false);

// Cloudflare setup
set('cloudflare', [
    'api_key' => env('CLOUDFLARE_API_KEY'),
    'email' => env('CLOUDFLARE_EMAIL'),
    'domain' => env('CLOUDFLARE_DOMAIN')
]);

task('setup:vars', function () {
    // http user
    $user = "ubuntu";

    set('http_user', $user);
    set('http_group', $user);
    set('tmp_path', '/tmp/deployer-' . get('theme_name'));
})->desc('Setup variables');

/**
 * Backup all shared files and directories
 */
task('setup:backup', function () {
    $currentPath = '{{deploy_path}}/current';
    $tmpPath = get('tmp_path');

    // Delete tmp dir if it exists.
    run("if [ -d $tmpPath ]; then rm -R $tmpPath; fi");

    // Create tmp dir.
    run("mkdir -p $tmpPath");

    foreach (get('shared_dirs') as $dir) {
        // Check if the shared dir exists.
        if (test("[ -d $(echo $currentPath/$dir) ]")) {
            // Clean up
            run("rm -rf $tmpPath/$dir");
            run("rm -rf $tmpPath/" . dirname($dir));

            // Create tmp shared dir.
            // run("mkdir -p $tmpPath/$dir");
            // run("rm -rf $tmpPath/$dir");
            run("mkdir -p $tmpPath/" . dirname($dir));

            // Copy shared dir to tmp shared dir.
            // echo "rm -f $tmpPath/" . dirname($dir);
            run("cp -rv $currentPath/$dir $tmpPath/" . dirname($dir));
        }
    }

    foreach (get('shared_files') as $file) {
        // If shared file exists, copy it to tmp dir.
        run("if [ -f $(echo $currentPath/$file) ]; then cp $currentPath/$file $tmpPath/$file; fi");
    }
})->desc('Backup all shared files and directories');


/**
 * Purge all files from the deploy path directory
 */
task('setup:purge', function () {
    // Delete everything in deploy dir.
    // run('rm -R {{deploy_path}}/*');
    run('rm -f {{deploy_path}}/current');
    run('rm -Rf {{deploy_path}}/releases');
})->desc('Purge all files from the deploy path directory');


/**
 * Restore backup of shared files and directories
 */
task('setup:restore', function() {
    $sharedPath = "{{deploy_path}}/shared";
    $tmpPath = get('tmp_path');

    foreach (get('shared_dirs') as $dir) {
        // Create shared dir if it does not exist.
        if (!test("[ -d $sharedPath/$dir ]")) {
            // Create shared dir if it does not exist.
            run("mkdir -p $sharedPath/$dir");
        }

        // If tmp shared dir exists, copy it to shared dir.
        run("if [ -d $(echo $tmpPath/$dir) ]; then cp -rv $tmpPath/$dir $sharedPath/" . dirname($dir) . "; fi");
    }

    foreach (get('shared_files') as $file) {
        // If tmp shared file exists, copy it to shared dir.
        run("if [ -f $(echo $tmpPath/$file) ]; then cp $tmpPath/$file $sharedPath/$file; fi");
    }
})->desc('Restore backup of shared files and directories');


/**
 * Configure known_hosts for git repository
 */
task('setup:known_hosts', function () {
    $repository = get('repository');
    $host = '';

    if (filter_var($repository, FILTER_VALIDATE_URL) !== FALSE) {
        $host = parse_url($repository, PHP_URL_HOST);
        // } elseif (preg_match('/^git@(?P<host>\w+?\.\w+?):/i', $repository, $matches)) {
    } elseif (preg_match('/^git@(.*):/i', $repository, $matches)) {
        $host = $matches[1];
    }

    if (empty($host)) {
        throw new \RuntimeException('Couldn\'t parse host from repository.');
    }

    run("ssh-keyscan -H -T 10 $host >> ~/.ssh/known_hosts");
})->desc('Configure known_hosts for git repository');

/**
 * Setup success message
 */
task('setup:success', function () {
    Deployer::setDefault('terminate_message', '<info>Successfully setup!</info>');
})->once()->setPrivate();


/**
 * Permission fix on writable directories
 */
task('deploy:writable:chown', function () {
    $sharedPath = "{{deploy_path}}/shared";
    $currentPath = '{{deploy_path}}/current';
    // shared dirs
    foreach (get('shared_dirs') as $dir) {
        // Check if the shared dir exists.
        if (test("[ -d $(echo $sharedPath/$dir) ]")) {
            run("sudo chown www-data:www-data -R $sharedPath/$dir");
        }
    }
    // writable dirs
    foreach (get('writable_dirs') as $dir) {
        // Create shared dir if does exist.
        if (test("[ -d $currentPath/$dir ]")) {
            run("sudo chown www-data:www-data -R $currentPath/$dir");
        }
    }
})->desc('Permission fix on writable directories');

/**
 * Generate PHP user.ini
 */
task('php:ini', function () {
    $deployPath = "{{deploy_path}}/current/web";
    run("touch $deployPath/.user.ini && sudo chown www-data:www-data $deployPath/.user.ini");
})->desc('Generate PHP user.ini');

/**
 * Push theme's public assets
 */
task('deploy:theme:public', function () {
    $deployPath = "{{deploy_path}}/current";
    // TODO: Assignable
    $vendorAssetsPath = "$deployPath/vendor/gorated/wp-chatgenie-public-builds";
    $assetsPath = "$deployPath/web/app/themes/{{theme_name}}";
    if (!test("[ -d $assetsPath/public ]")) {
        run("mkdir -p $assetsPath/public");
    }
    run("cp -rf $vendorAssetsPath/css $assetsPath/public/css/");
    run("cp -rf $vendorAssetsPath/images $assetsPath/public/images/");
    run("cp -rf $vendorAssetsPath/js $assetsPath/public/js/");
    run("cp -f $vendorAssetsPath/composer.json $assetsPath/public/");
    run("cp -f $vendorAssetsPath/composer.lock $assetsPath/public/");
})->desc('Push theme\'s public assets');

/**
 * Reload php-fpm service
 */
task('php-fpm:reload', function () {
    run('sudo /etc/init.d/php7.2-fpm reload');
})->desc('Reload php-fpm service');

/**
 * Reload nginx service
 */
task('nginx:reload', function () {
    run('sudo /etc/init.d/nginx reload');
})->desc('Reload nginx service');

/**
 * Reload varnish service
 */
task('varnish:reload', function () {
    run('sudo /etc/init.d/varnish reload');
})->desc('Reload varnish service');


/**
 * Deploy task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:writable:chown',
    'deploy:symlink',
    'deploy:unlock',
    'php:ini',
    'deploy:theme:public',
    'cleanup',
    'nginx:reload',
    'php-fpm:reload',
    // 'cloudflare'
])->desc('Deploy your Bedrock project');
after('deploy', 'success');


/**
 * Setup task
 */
task('setup', [
    'setup:backup',
    'setup:purge',
    'deploy:prepare',
    'setup:restore',
    'setup:known_hosts',
])->desc('Setup your Bedrock project');
after('setup', 'setup:success');

before('db:cmd:pull', 'env:uri');
before('db:cmd:push', 'env:uri');

after('deploy', 'deploy:cleanup');

before('deploy', 'setup:vars');
before('setup', 'setup:vars');
before('deploy:unlock', 'setup:vars');
before('uploads:sync', 'setup:vars');
before('db:cmd:push', 'setup:vars');
before('db:cmd:pull', 'setup:vars');
