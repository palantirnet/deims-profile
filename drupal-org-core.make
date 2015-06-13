api = 2
core = 7.x

; Drupal core
projects[drupal][type] = core
projects[drupal][version] = 7.36

; Ensure that hook_field_presave() is run for default field values.
; @see https://drupal.org/node/1899498
projects[drupal][patch][] = "http://drupal.org/files/1899498-field-default-value-invoke-presave.patch"
; Add support for formatter weights.
; @see https://drupal.org/node/1982776
projects[drupal][patch][] = "http://drupal.org/files/1982776-field-formatter-weight-do-not-test_0.patch"
; Uncomment settings.local.php support in settings.php
; @see https://drupal.org/node/1118520
projects[drupal][patch][] = "http://drupal.org/files/1118520-settings-local-uncommented-do-not-test.patch"
; Fixed error due to inserting null or 0 into postgreSQL sequence field
; @see https://www.drupal.org/node/2504323
projects[drupal][patch][] = "https://www.drupal.org/files/issues/drupal_core_7.x-pgsql-seq-ge1-2504323-4.patch"
