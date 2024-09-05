# merge_templates

## Overview

`merge_templates.php` is a PHP CLI script designed to facilitate the merging and copying of files between two directories, while preserving the directory structure. The script supports advanced file merging features, allowing content to be inserted into specific locations within existing files, based on placeholders. It also provides flexibility in controlling how files are copied and merged through various command-line options.

## Features

- **Recursive Directory Copying:** Copies files and directories from the source to the target, maintaining the original structure.
- **Conditional File Overwriting:** Allows control over whether existing files in the target directory are overwritten.
- **Content Merging:** Inserts content into specific placeholders within target files, based on source files with a predefined format.
- **Duplicate Control:** Optionally prevents duplicate content from being inserted during the merge process.
- **Highly Configurable:** Offers multiple command-line options to customize behavior.

## Usage

### Basic Command

```bash
php merge_templates.php [OPTIONS] path_source_dir/ path_target_dir/
```

### Options

- `--paste-files=true|false` (default: `true`): 
  - `true`: Copies files from `path_source_dir/` to `path_target_dir/`.
  - `false`: Skips file copying and directory creation.

- `--paste-files-replace=true|false` (default: `true`): 
  - `true`: Overwrites existing files in `path_target_dir/` during the copy process.
  - `false`: Skips copying if a file with the same name already exists in the target directory.

- `--merge-contents=true|false` (default: `true`): 
  - `true`: Processes files of type `merge_add_content` to insert content into placeholders within target files.
  - `false`: Skips processing these files.

- `--allow-merge-contents-dups=true|false` (default: `false`): 
  - `true`: Allows the insertion of duplicate content within placeholders during the merge process.
  - `false`: Checks existing placeholder content in the target file, and skips inserting duplicates.

- `--help`, `-h`: Displays help information about the script.
- `--version`, `-v`: Displays the script version.

## Example Scenarios

### Scenario 1: Basic Copy and Merge

Copy files from `source/` to `target/`, replacing existing files and merging content where applicable:

```bash
php merge_templates.php path_source_dir/ path_target_dir/
```

### Scenario 2: Prevent File Overwriting

Copy files without replacing existing files in the target directory:

```bash
php merge_templates.php --paste-files-replace=false path_source_dir/ path_target_dir/
```

### Scenario 3: Content Merging Only

Merge content from `merge_add_content` files without copying any other files:

```bash
php merge_templates.php --paste-files=false --merge-contents=true path_source_dir/ path_target_dir/
```

### Scenario 4: No Duplicate Content in Placeholders

Merge content but prevent the insertion of duplicate content within placeholders:

```bash
php merge_templates.php --allow-merge-contents-dups=false path_source_dir/ path_target_dir/
```

## File Format for Content Merging

To merge content into an existing file in the target directory, create a file in the source directory with the following format:

**File Name Format:**  
`+<number>_<target-filename>`

**Example File Content (`+1_example.txt`):**
```
###placeholder_start
Content line 1 to insert
Content line 2 to insert
###placeholder_end
```

In the target file (`example.txt`), the script will search for `###placeholder_start` and `###placeholder_end`, and insert the new content between these markers.

**Example Target File (`example.txt`) Before Merge:**
```
###placeholder_start
Existing content...
###placeholder_end
```

**Example Target File (`example.txt`) After Merge:**
```
###placeholder_start
Content line 1 to insert
Content line 2 to insert
Existing content...
###placeholder_end
```

## Error Handling

- If a target file for merging does not exist, the script will throw an exception.
- The script ensures that if duplicate content is not allowed, it will skip inserting content that already exists within the placeholder.

## License

This script is released under the MIT License.



