*Please note this is still a slight work in progress, but everything important is present.*

This automates modifying/updating/etc. anything relating to Support_Updater. The only thing devs still have to update is `version.json` in their addons... to an extent.

```
{"version":"foo"}
```

This is all that's now required in them.   This script will take care of the rest in all addons and all branches server-side.

Repositories are automatically generated in JSON and updated every time it's accessed.
## Configuration
`$setting["name"]` is the repository name  
`$setting["addon_folder"]` is the directory where addon files are stored *(relative to `index.php`)*.  
`$setting["root_url"]` is where the repository is publicly accessed.

## Directory Structure
**YOU MUST FOLLOW THIS STRUCTURE!** Failure to do so may leave you with an invalid repository.

```
root directory
    |
    $setting['addon_folder']
        |
        addon name
            |
            branch/channel
```
e.g. `webroot/src/Client_SUTest/release`

Zip files are not used, simply upload your source files. Downloads are generated on-the-fly from `download.php`.

## Downloads
Downloads will come from `download.php`, and are accessed via 2 get parameters:

- `id`: ID number of the addon (auto-generated)
- `channel`: Channel/branch of development (e.g. *stable, dev, unstable, release, testing, etc.*)

## Requirements
- Webserver with PHP and file access
  - ZIP extension must be enabled