; Base Modules
;----------------

; themes
projects[adminimal_theme][type] = theme
projects[adminimal_theme][subdir] = contrib

; Administration
projects[admin_toolbar][subdir] = contrib
projects[adminimal_admin_toolbar][subdir] = sandbox
projects[adminimal_admin_toolbar][download][type] = "git"
projects[adminimal_admin_toolbar][download][url] = "http://git.drupal.org/sandbox/energee/2690521.git"
projects[adminimal_admin_toolbar][download][branch] = "8.x-1.x"

projects[menu_link_attributes][subdir] = contrib
projects[fpa][subdir] = contrib
projects[libraries][subdir] = contrib
projects[entity_clone][subdir] = contrib
projects[coffee][subdir] = contrib
projects[contact_storage][subdir] = contrib

; Fields
projects[field_group][subdir] = contrib
projects[fff][subdir] = contrib
projects[fences][subdir] = contrib
projects[image_formatter_link_to_image_style][subdir] = contrib
projects[image_styles_mapping][subdir] = contrib
projects[markup][subdir] = contrib
projects[filefield_paths][subdir] = contrib
projects[rdfui][subdir] = contrib

; Text Formats and Editors
projects[editor_file][subdir] = contrib
projects[editor_advanced_link][subdir] = contrib
projects[linkit][subdir] = contrib

; Taxonomies
projects[taxonomy_access_fix][subdir] = contrib
projects[taxonomy_machine_name][subdir] = contrib

; Multilanguage
; projects[lang_dropdown][subdir] = contrib

; Paths
projects[pathauto][subdir] = contrib
projects[ctools][subdir] = contrib

; SEO
projects[hreflang][subdir] = contrib

; Tokens
projects[token][subdir] = contrib

; UX
projects[extlink][subdir] = contrib
projects[logouttab][subdir] = contrib

; Help
projects[advanced_help][subdir] = contrib

; Performance
; included in 8.2.x core
; projects[big_pipe][subdir] = contrib


; Backups
projects[backup_migrate][subdir] = contrib
projects[backup_migrate][patch][] = https://www.drupal.org/files/issues/backup_migrate-remove_translatetrait-2713531-1-8.patch
