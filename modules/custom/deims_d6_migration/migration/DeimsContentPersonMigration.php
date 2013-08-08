<?php

/**
 * @file
 * Definition of DeimsContentPersonMigration.
 */

class DeimsContentPersonMigration extends DrupalNode6Migration {
  protected $dependencies = array('DeimsContentOrganization');

  public function __construct(array $arguments) {
    $arguments += array(
      'description' => '',
      'source_connection' => 'drupal6',
      'source_version' => 6,
      'source_type' => 'person',
      'destination_type' => 'person',
      'user_migration' => 'DeimsUser',
    );

    parent::__construct($arguments);

    // This content type does not have a body field.
    $this->removeFieldMapping('body');
    $this->removeFieldMapping('body:language');
    $this->removeFieldMapping('body:summary');
    $this->removeFieldMapping('body:format');
    $this->addUnmigratedSources(array('body', 'teaser', 'format'));

    // Add our mappings.
    $this->addFieldMapping('field_phone', 'field_person_phone');
    $this->addFieldMapping('field_email', 'field_person_email');
    $this->addFieldMapping('field_fax', 'field_person_fax');
    $this->addFieldMapping('field_user_account', 'field_person_uid')
      ->sourceMigration('Users');
    $this->addFieldMapping('field_list_in_directory', 'field_person_list');
    $this->addFieldMapping('field_person_id', 'field_person_personid');
    $this->addFieldMapping('field_person_role', 'field_person_role');
    $this->addFieldMapping('field_person_title', 'field_person_title');

    $this->addFieldMapping('field_name', 'field_person_first_name');
    $this->addFieldMapping('field_name:family', 'field_person_last_name');

    $this->addFieldMapping('field_address', 'field_person_country');
    $this->addFieldMapping('field_address:administrative_area', 'field_person_state');
    $this->addFieldMapping('field_address:locality', 'field_person_city');
    $this->addFieldMapping('field_address:postal_code', 'field_person_zipcode');
    $this->addFieldMapping('field_address:thoroughfare', 'field_person_address');

    // Values for field_organization provided in prepare()).
    $this->addFieldMapping('field_organization', NULL)
      ->description('Provided in prepare().');

    $this->addUnmigratedSources(array(
      'field_person_organization', // Migrated in prepare().
      'field_person_fullname',
      'field_person_pubs',
    ));
    $this->addUnmigratedDestinations(array(
      'field_address:sub_administrative_area',
      'field_address:dependent_locality',
      'field_address:premise',
      'field_address:sub_premise',
      'field_address:organisation_name',
      'field_address:name_line',
      'field_address:first_name',
      'field_address:last_name',
      'field_address:data',
      'field_name:given',
      'field_name:middle',
      'field_name:generational',
      'field_name:credentials',
      'field_person_title:language',
    ));
  }

  public function prepareRow($row) {
    parent::prepareRow($row);

    // Convert values from 'Yes' and 'No' to integers 1 and 0, respectively.
    $row->field_person_list = (!empty($row->field_person_list) && $row->field_person_list == 'Yes' ? 1 : 0);

    // Fix empty email values.
    switch ($row->field_person_email) {
      case 'none@none.com':
      case 'not@known.edu':
      case 'unknown.email@unknown.edu':
        $row->field_person_email = NULL;
    }

    // Fix country values.
    switch ($row->field_person_country) {
      case 'USA':
      case 'United States of America':
        $row->field_person_country = 'United States';
        break;
    }

    // Convert a country name into a country code for addressfield.
    if (!empty($row->field_person_country)) {
      $country_code = $this->getCountryCode($row->field_person_country);
      if (!$country_code) {
        $country_code = $this->getDefaultCountryCode();
        // Default the country to the US to ensure that this field is saved.
        $this->queueMessage("Invalid country value '{$row->field_person_country}' has been changed to default {$country_code} so the address field will save.", MigrationBase::MESSAGE_INFORMATIONAL);
      }
      $row->field_person_country = $country_code;
    }
    elseif (!empty($row->field_person_address) || !empty($row->field_person_city) || !empty($row->field_person_state)) {
      // Default the country to the US to ensure that this field is saved.
      $row->field_person_country = $this->getDefaultCountryCode();
      $this->queueMessage("Empty country value with non-empty address has been changed to default {$row->field_person_country} so the address field will save.", MigrationBase::MESSAGE_INFORMATIONAL);
    }

    if ($row->field_person_zipcode == 0) {
      $row->field_person_zipcode = NULL;
    }
  }

  public function prepare($node, $row) {
    $node->field_organization[LANGUAGE_NONE] = $this->getOrganization($node, $row);

    // Force the auto_entitylabel module to leave $node->title alone.
    $node->auto_entitylabel_applied = TRUE;

    // Remove any empty or illegal delta field values.
    EntityHelper::removeInvalidFieldDeltas('node', $node);
    EntityHelper::removeEmptyFieldValues('node', $node);
  }

  public function getOrganization($node, $row) {
    $field_values = array();

    // Search for an already migrated organization entity with the same title
    // and link value.
    if (!empty($row->field_person_organization)) {
      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', 'node');
      $query->entityCondition('bundle', 'organization');
      $query->propertyCondition('title', $row->field_person_organization);
      $results = $query->execute();
      if (!empty($results['node'])) {
        $field_values[] = array('target_id' => reset($results['node'])->nid);
      }
    }

    return $field_values;
  }

  /**
   * Convert a country name to it's country two-character code.
   *
   * @param string $country_name
   *   The country name.
   *
   * @return string
   *   The two-letter country code if found, or FALSE if the country was not
   *   found.
   */
  public function getCountryCode($country_name) {
    include_once DRUPAL_ROOT . '/includes/locale.inc';
    $countries = country_get_list();
    if (isset($countries[$country_name])) {
      // Do nothing. Country is already a valid code.
      return $country_name;
    }
    elseif ($code = array_search($country_name, $countries)) {
      return $code;
    }
    else {
      return FALSE;
    }
  }

  public function getDefaultCountryCode() {
    static $default = NULL;

    if (!isset($default)) {
      $instance = field_info_instance('node', 'field_address', 'person');
      if (!empty($instance['default_value'][0]['country'])) {
        $default = $instance['default_value'][0]['country'];
      }
      else {
        $default = variable_get('site_default_country', 'US');
      }
    }

    return $default;
  }
}
