<?php

/**
 * @file
 * Node Auto Expire.
 *
 * Adding fields created by the module to views.
 */

/**
 * Implements hook_views_data().
 */
function node_auto_expire_views_data() {

  // Define the return array.
  $data = [];

  // The outermost keys of $data are Views table names, which should usually
  // be the same as the hook_schema() table names.
  $data['node_auto_expire'] = [];

  // The value corresponding to key 'table' gives properties of the table
  // itself.
  $data['node_auto_expire']['table'] = [];

  // Within 'table', the value of 'group' (translated string) is used as a
  // prefix in Views UI for this table's fields, filters, etc. When adding
  // a field, filter, etc. you can also filter by the group.
  $data['node_auto_expire']['table']['group'] = t('Node Auto Expire');

  // Within 'table', the value of 'provider' is the module that provides schema
  // or the entity type that causes the table to exist. Setting this ensures
  // that views have the correct dependencies. This is automatically set to the
  // module that implements hook_views_data().
  $data['node_auto_expire']['table']['provider'] = 'node_auto_expire';

  // An implicit, automatic relationship to other tables,
  // meaning that when the other table is available in a view (either as the
  // base table or through a relationship), this table's fields, filters, etc.
  // are automatically made available without having to add an additional
  // relationship.
  $data['node_auto_expire']['table']['join'] = [
    // Within the 'join' section, list one or more tables to automatically
    // join to. Here every time 'node_field_data' is available in
    // a view, 'node_auto_expire' will be too.
    'node_field_data' => [
      // Primary key field in node_field_data to use in the join.
      'left_field' => 'nid',
      // Foreign key field in node_auto_expire to use in the join.
      'field' => 'nid',
    ],
  ];

  // Other array elements at the top level of the table's array
  // The array keys are the names (unique within the table) used by Views
  // for the fields, usually equal to the database field names.
  //
  // Each field entry must have the following elements:
  // - title: Translated label for the field in the UI.
  // - help: Description of the field in the UI.
  //
  // Node ID field.
  $data['node_auto_expire']['nid'] = [
    'title' => t('Node ID'),
    'help' => t('The node the expiration date applies to.'),
    // Define a relationship to the node_field_data table, so views whose
    // base table is node_auto_expire can add a relationship to nodes.
    'relationship' => [
      // Views name of the table to join to for the relationship.
      'base' => 'node_field_data',
      // Database field name in the other table to join on.
      'field' => 'nid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t('Node Auto Expire'),
    ],
  ];

  // Warned field.
  $data['node_auto_expire']['warned'] = [
    'title' => t('Warned'),
    'help' => t('Whether the author has been warned for expiration since the last extension.'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'boolean',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'boolean',
      // Override the generic field title, so that the filter uses a different
      // label in the UI.
      'label' => t('Warned'),
      // Override the default BooleanOperator filter handler's 'type' setting,
      // to display this as a "Yes/No" filter instead of a "True/False" filter.
      'type' => 'yes-no',
      // Override the default Boolean filter handler's 'use_equal' setting, to
      // make the query use 'boolean_field = 1' instead of 'boolean_field <> 0'.
      'use_equal' => TRUE,
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
  ];

  // Extended field.
  $data['node_auto_expire']['extended'] = [
    'title' => t('Extended'),
    'help' => t('The number of times the node has been extended.'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'numeric',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'numeric',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
  ];

  // Expiration date field.
  $data['node_auto_expire']['expire'] = [
    'title' => t('Expiration date'),
    'help' => t('The date and time of expiration for this node.'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'date',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'date',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'date',
    ],
  ];

  // Extend link field.
  $data['node_auto_expire']['extend_node'] = [
    'field' => [
      'title' => t('Extend link'),
      'help' => t('Provide a simple link to extend the node.'),
      'id' => 'node_auto_expire_extend_link',
    ],
  ];

  return $data;

}
