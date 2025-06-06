
# Site Settings and Labels Module Readme

This module provides a way to let clients manage settings you define without
affecting the configuration of the site (ie, as 'Content'). It does the
following:

- provides an interface for administrators to set up settings
- allows administrators to add one or more fields to each setting
- allows the administrator to decide whether to allow multiple occurrences
  of the same setting
- allows the administrator to group settings together
- provides a simple interface for anyone with the new permissions to add, edit,
  and delete site settings content
- caches the data and efficiently makes it available in every twig template
- stores the configuration for each setting as config so site builders can
  version control the available settings and their fields while keeping the
  content added to each setting outside of version control
- makes the settings available as tokens for use anywhere tokens are used
- allows quick mass replication of settings and labels
- provides a simple block to output site settings, further customizable in
  templates as described below

## Example use cases

- You want to let a client add one or more social networks with links to
  their profile pages and you want to show that on a few templates such as the
  header and footer
- You want to let a client add some general settings to control how or where
  things are displayed
- You want to allow a client to specify some general labels or settings you use
  throughout your site
- You want to let a client add some general settings that they can reuse as
  tokens in for instance automated emails

## Installation

To install this module, place it in your modules folder and enable it on the
modules page.

## How to set up your settings

1. Set up your settings and add fields to your settings
2. Manage the Teaser to control the display in the Admin > Content > Site Settings list
3. Manage the default Display Mode to control the display in your theme
4. Use the twig functions to render the Site Settings in your theme
5. Export your settings config
6. Deploy your work to your production site
7. Choose your desired permissions in the admin > people > permissions tab to
   grant access to site editors for instance.

## How to access the settings in twig templates

### (Recommended) Full Site Settings Loader using Twig Functions

_The Full Site Settings Loader is a simple wrapper around Entity Type Manager Storage. Rendering Site Settings into your templates is now primarily via Twig functions._

#### Twig function site_setting()
`{{ site_setting(6) }}` to render Site Setting ID 6 in default display mode. Can also take a UUID instead of an ID.

#### Twig function site_settings_by_name()
`{{ site_settings_by_name('my_machine_name') }}` to render one or more entities by site setting machine name (type) in default display mode.

#### Twig function all_site_settings()
```twig
{% set all_site_settings = all_site_settings() %}
{{ all_site_settings }}
```
to render all Site Settings in default display mode.

#### Twig function site_settings_by_group()
```twig
{% set site_settings = site_settings_by_group('My Group') %}
{{ site_settings }}
```
to render all Site Settings within a specified group in default display mode.

### (Not recommended) Flattened Site Settings Loader

_The Flattened Site Settings Loader is there to make an easier upgrade path to the 2.x branch. This section documents how to use it. The recommended approach is to use the Twig functions. The Twig functions can be used even while the Flattened Loader is active allowing a transition to the new recommended loader_

Debug your settings when debug is enabled <https://www.drupal.org/docs/8/theming/twig/debugging-twig-templates>
via `{{ dump(site_settings) }}` or `{{ dump(site_settings.your_settings_group.your_setting_name) }}` for instance.

Access a non-repeating variable with one field like so:
`{{ site_settings.your_settings_group.your_setting_name }}`

Access a non-repeating variable with multiple fields like so:
`{{ site_settings.your_settings_group.your_setting_name.field_title }}` and
`{{ site_settings.your_settings_group.your_setting_name.field_subtitle }}`

Access a non-repeating variable with multiple fields and complex field settings:
`{{ site_settings.your_settings_group.your_setting_name.field_date.value }}`
`{{ site_settings.your_settings_group.your_setting_name.field_date.options }}` etc

Access a repeating variable with one field like so:
`{% for site_setting in site_settings.your_settings_group.your_setting_name %}
{{ site_setting }}
{% endfor %}`

#### Configuration

Optionally change the variable name your twig files will receive the site settings into at
`/admin/config/site-settings/config`. By default, your variables are in `{{ dump(site_settings) }}`.

## How to access the settings in php files

Use the site settings loader active plugin:
```
/** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
$plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
$site_settings = $plugin_manager->getActiveLoaderPlugin();
$settings = $site_settings->loadAll();
```
or
```
/** @var \Drupal\site_settings\SiteSettingsLoaderPluginManager $plugin_manager */
$plugin_manager = \Drupal::service('plugin.manager.site_settings_loader');
$site_settings = $plugin_manager->getActiveLoaderPlugin();
$settings = $site_settings->loadByGroup('your_settings_group');
```

## How to access the settings via the token browser

Open the token browser anywhere and you'll find the settings are globally
available under `Site settings and labels`.

## How to replicate settings rapidly

Browse to the manage settings page and choose the 'replicate' operation
from the setting you wish to use as the base for replications. Add as
many rows as desired and specify the new machine names, labels, and
how you would like the settings grouped.

## Feedback on this module

Please add issues with feature requests as well as feedback on the existing
functionality.

## Supporting organizations

Initial development of this module was sponsored by Fat Beehive until mid-2018.

## Maintainers

- Scott Euser (scott_euser) - <https://www.drupal.org/u/scott_euser>