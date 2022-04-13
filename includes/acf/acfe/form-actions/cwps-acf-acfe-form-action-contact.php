<?php
/**
 * "Contact" ACFE Form Action Class.
 *
 * Handles the "Contact" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "Contact" ACFE Form Action Class.
 *
 * A class that handles the "Contact" ACFE Form Action.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Contact extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $form The ACFE Form object.
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $action_name The unique name of the Form Action.
	 */
	public $action_name = 'cwps_contact';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_contact_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_contact_action_';

	/**
	 * Public Contact Fields to add.
	 *
	 * These are not mapped for Post Type Sync, so need to be added.
	 *
	 * @since 0.5.1
	 * @access public
	 * @var array $fields_to_add The Public Contact Fields to add.
	 */
	public $fields_to_add = [
		'display_name' => 'text',
		'id' => 'number',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acfe = $parent->acfe;
		$this->form = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->action_label = __( 'CiviCRM Contact action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'CiviCRM Contact', 'civicrm-wp-profile-sync' );

		// Register hooks.
		$this->register_hooks();

		// Init parent.
		parent::__construct();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



	/**
	 * Configure this object.
	 *
	 * @since 0.5
	 */
	public function configure() {

		// Get the public Contact Fields for all top level Contact Types.
		$this->public_contact_fields = $this->civicrm->contact_field->get_public_fields();

		// Prepend the ones that are needed in ACFE Forms (i.e. Contact ID).
		foreach ( $this->fields_to_add as $name => $field_type ) {
			array_unshift( $this->public_contact_fields['common'], $this->plugin->civicrm->contact_field->get_by_name( $name ) );
		}

		// Populate public mapping Fields.
		foreach ( $this->public_contact_fields as $contact_type => $fields_for_type ) {
			foreach ( $fields_for_type as $field ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Get the Custom Fields for all Contact Types.
		$this->custom_fields = $this->plugin->civicrm->custom_group->get_for_all_contact_types();
		$this->custom_field_ids = [];

		// Populate mapping Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
					// Also build Custom Field IDs.
					$this->custom_field_ids[] = (int) $custom_field['id'];
				}
			}
		}

		// Get Location Types.
		$this->location_types = $this->plugin->civicrm->address->location_types_get();

		// Get default Location Type.
		$this->location_type_default = false;
		foreach ( $this->location_types as $location_type ) {
			if ( ! empty( $location_type['is_default'] ) ) {
				$this->location_type_default = $location_type['id'];
				break;
			}
		}

		// Get the public Email Fields.
		$this->email_fields = $this->civicrm->email->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->email_fields as $email_field ) {
			$this->mapping_field_filters_add( 'email_' . $email_field['name'] );
		}

		// Email Ref Field.
		$this->mapping_field_filters_add( 'email_conditional' );

		// Get Website Types.
		$this->website_types = $this->plugin->civicrm->website->types_options_get();

		// Get the public Website Fields.
		$this->website_fields = $this->civicrm->website->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->website_fields as $website_field ) {
			$this->mapping_field_filters_add( 'website_' . $website_field['name'] );
		}

		// Website Ref Field.
		$this->mapping_field_filters_add( 'website_conditional' );

		// Get the public Address Fields.
		$this->address_fields = $this->civicrm->address->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->address_fields as $address_field ) {
			$this->mapping_field_filters_add( 'address_' . $address_field['name'] );
		}

		// Get the Custom Fields for all Addresses.
		$this->address_custom_fields = $this->plugin->civicrm->custom_group->get_for_addresses();

		// Populate Address mapping Fields.
		foreach ( $this->address_custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
				}
			}
		}

		// Address Ref Field.
		$this->mapping_field_filters_add( 'address_conditional' );

		// Get Phone Types.
		$this->phone_types = $this->plugin->civicrm->phone->phone_types_get();

		// Get the public Phone Fields.
		$this->phone_fields = $this->civicrm->phone->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->phone_fields as $phone_field ) {
			$this->mapping_field_filters_add( 'phone_' . $phone_field['name'] );
		}

		// Phone Ref Field.
		$this->mapping_field_filters_add( 'phone_conditional' );

		// Get Instant Messenger Providers.
		$this->im_providers = $this->civicrm->im->im_providers_get();

		// Get the public Instant Messenger Fields.
		$this->im_fields = $this->civicrm->im->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->im_fields as $im_field ) {
			$this->mapping_field_filters_add( 'im_' . $im_field['name'] );
		}

		// Instant Messenger Ref Field.
		$this->mapping_field_filters_add( 'im_conditional' );

		// Group Ref Field.
		$this->mapping_field_filters_add( 'group_conditional' );

		// Get the Free Membership Types.
		$this->membership_types = $this->civicrm->membership->types_get_free();

		// Configure Membership Fields if there are some.
		if ( ! empty( $this->membership_types ) ) {

			// Get the public Membership Fields.
			$this->membership_fields = $this->civicrm->membership->civicrm_fields_get( 'public' );

			// Populate public mapping Fields.
			foreach ( $this->membership_fields as $membership_field ) {
				$this->mapping_field_filters_add( 'membership_' . $membership_field['name'] );
			}

			// Membership Ref Field.
			$this->mapping_field_filters_add( 'membership_conditional' );

		}

		// Get the public Note Fields.
		$this->note_fields = $this->civicrm->note->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->note_fields as $note_field ) {
			$this->mapping_field_filters_add( 'note_' . $note_field['name'] );
		}

		// Add Note Ref Field.
		$this->mapping_field_filters_add( 'note_conditional' );

		// Get the public Attachment Fields.
		$this->attachment_fields = $this->civicrm->attachment->civicrm_fields_get( 'public' );

		// Populate public mapping Fields.
		foreach ( $this->attachment_fields as $attachment_field ) {
			$this->mapping_field_filters_add( 'attachment_' . $attachment_field['name'] );
		}

		// Attachment File and Conditional Fields.
		$this->mapping_field_filters_add( 'attachment_file' );
		$this->mapping_field_filters_add( 'attachment_conditional' );

		// Tag Ref Field.
		$this->mapping_field_filters_add( 'tag_conditional' );

		// Get the public Relationship Fields.
		$this->relationship_fields = $this->civicrm->relationship->civicrm_fields_get( 'public' );

		// Populate public mapping Relationship Fields.
		foreach ( $this->relationship_fields as $relationship_field ) {
			$this->mapping_field_filters_add( $relationship_field['name'] );
		}

		// Build the choices for the Relationship Types.
		$choices = [];
		$relationship_types = $this->civicrm->relationship->types_get_all();
		foreach ( $relationship_types as $relationship ) {
			if ( $relationship['label_a_b'] !== $relationship['label_b_a'] ) {
				$choices[ $relationship['contact_type_a'] ][ $relationship['id'] . '_ab' ] = esc_html( $relationship['label_a_b'] );
				$choices[ $relationship['contact_type_b'] ][ $relationship['id'] . '_ba' ] = esc_html( $relationship['label_b_a'] );
			} else {
				$choices[ $relationship['contact_type_a'] ][ $relationship['id'] . '_equal' ] = esc_html( $relationship['label_a_b'] );
			}
		}

		// Assign to a property.
		$this->relationship_choices = $choices;

		// Get the Custom Fields for all Relationship Types.
		$this->relationship_custom_fields = $this->plugin->civicrm->custom_group->get_for_relationships();

		// Populate Relationship mapping Fields.
		foreach ( $this->relationship_custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
				}
			}
		}

		// Add Contact Action Reference Field to ACF Model.
		$this->js_model_contact_reference_field_add( $this->field_name . 'relationship_action_ref' );

		// Contact Conditional Field.
		$this->mapping_field_filters_add( 'contact_conditional' );

	}



	/**
	 * Performs tasks when the Form the Action is attached to is loaded.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post in which the Form has been embedded.
	 * @param string $action The customised name of the action.
	 */
	public function load( $form, $current_post_id, $action ) {

		// Check if this Action is loading values.
		$autoload_enabled = get_sub_field( $this->field_key . 'autoload_enabled' );

		// Skip if not.
		if ( ! $autoload_enabled ) {
			return $form;
		}

		// Init Contact and Relationships.
		$contact = [];

		// Try finding the Contact ID.
		$contact_id = $this->form_contact_id_get_submitter();

		// The Contact ID may already exist in the Query Vars.
		if ( ! $contact_id ) {
			$contact_id = $this->form_contact_id_get_existing( $form, $current_post_id, $action );
		}

		// Get the Relationships.
		$relationships = $this->form_relationship_data( $form, $current_post_id, $action );

		// Try finding the Contact ID by Relationship.
		if ( ! $contact_id ) {
			$contact_id = $this->form_contact_id_get_related( $relationships );
		}

		// Maybe get the Contact.
		if ( ! empty( $contact_id ) ) {
			$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );
		}

		// Bail if we don't find a Contact.
		if ( empty( $contact ) ) {
			return $form;
		}

		// Populate the Contact Fields.
		foreach ( $this->public_contact_fields as $contact_type => $fields_for_type ) {
			foreach ( $fields_for_type as $key => $field_for_type ) {
				$field = get_sub_field( $this->field_key . 'map_' . $field_for_type['name'] );
				$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
				if ( acf_is_field_key( $field ) && ! empty( $contact[ $field_for_type['name'] ] ) ) {
					$form['map'][ $field ]['value'] = $contact[ $field_for_type['name'] ];
				}
			}
		}

		// Get the Custom Field values for this Contact.
		$custom_field_values = $this->plugin->civicrm->custom_field->values_get_by_contact_id( $contact['id'], $this->custom_field_ids );
		foreach ( $custom_field_values as $custom_field_id => $custom_field_value ) {
			$contact[ 'custom_' . $custom_field_id ] = $custom_field_value;
		}

		// Handle population of Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Get the Group Field.
			$custom_group_field = get_sub_field( $this->field_key . 'custom_group_' . $custom_group['id'] );

			// Populate the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$field = $custom_group_field[ $this->field_name . 'map_' . $code ];
				$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
				if ( acf_is_field_key( $field ) ) {

					// Allow (string) "0" as valid data.
					if ( empty( $contact[ $code ] ) && $contact[ $code ] !== '0' ) {
						continue;
					}

					// Apply Contact value.
					$form['map'][ $field ]['value'] = $contact[ $code ];

					// Convert any "File" Custom Fields to WordPress Attachment IDs.
					if ( $custom_field['data_type'] === 'File' && ! empty( $contact[ $code ] ) ) {
						$civicrm_file = $this->civicrm->attachment->file_get_by_id( $contact[ $code ] );
						if ( ! empty( $civicrm_file ) ) {
							$attachment_id = $this->civicrm->attachment->query_by_file( $civicrm_file->uri, 'civicrm' );
							if ( ! empty( $attachment_id ) ) {
								$form['map'][ $field ]['value'] = $attachment_id;
							}
						}
					}

				}
			}

		}

		// Init retrieved Email data.
		$emails = [];

		// Get the raw Email Actions.
		$email_actions = get_sub_field( $this->field_key . 'email_repeater' );
		if ( ! empty( $email_actions ) ) {
			foreach ( $email_actions as $email_action ) {

				// Try and get the Email Record.
				$location_type_id = $email_action[ $this->field_name . 'map_email_location_type_id' ];
				$email = (array) $this->civicrm->email->email_get_by_location( $contact['id'], $location_type_id );
				if ( empty( $email ) ) {
					continue;
				}

				// Add to retrieved Email data.
				$emails[] = $email;

				// Populate the Email Fields.
				foreach ( $this->email_fields as $email_field ) {
					$field = $email_action[ $this->field_name . 'map_email_' . $email_field['name'] ];
					$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
					if ( acf_is_field_key( $field ) && ! empty( $email[ $email_field['name'] ] ) ) {
						$form['map'][ $field ]['value'] = $email[ $email_field['name'] ];
					}
				}

			}
		}

		// Get the raw Relationship Actions.
		$relationship_actions = get_sub_field( $this->field_key . 'relationship_repeater' );
		if ( ! empty( $relationship_actions ) ) {
			foreach ( $relationship_actions as $relationship_action ) {

				// Try and get the Relationship Record.
				$relationship = [];
				foreach ( $relationships as $relationship_data ) {
					$relationship_type = $relationship_action[ $this->field_name . 'relationship_type' ];
					$relationship_array = explode( '_', $relationship_type );
					$type_id = (int) $relationship_array[0];
					$direction = $relationship_array[1];
					if ( (int) $relationship_data['relationship_type_id'] !== (int) $type_id ) {
						continue;
					}
					$relationship = $relationship_data;
					break;
				}

				if ( empty( $relationship ) ) {
					continue;
				}

				// Populate the Relationship Fields.
				foreach ( $this->relationship_fields as $relationship_field ) {
					$field = $relationship_action[ $this->field_name . 'map_' . $relationship_field['name'] ];
					$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
					if ( acf_is_field_key( $field ) && ! empty( $relationship[ $relationship_field['name'] ] ) ) {
						$form['map'][ $field ]['value'] = $relationship[ $relationship_field['name'] ];
					}
				}

				// Handle population of Relationship Custom Fields.
				foreach ( $this->relationship_custom_fields as $key => $custom_group ) {

					// Get the Group Field.
					$custom_group_field = $relationship_action[ $this->field_name . 'relationship_custom_group_' . $custom_group['id'] ];

					// Populate the Relationship Custom Fields.
					foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
						$code = 'custom_' . $custom_field['id'];
						$field = $custom_group_field[ $this->field_name . 'map_' . $code ];
						$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
						if ( acf_is_field_key( $field ) ) {
							// Allow (string) "0" as valid data.
							if ( empty( $relationship[ $code ] ) && $relationship[ $code ] !== '0' ) {
								continue;
							}
							$form['map'][ $field ]['value'] = $relationship[ $code ];
						}
					}

				}

			}
		}

		// Init retrieved Website data.
		$websites = [];

		// Get the raw Website Actions.
		$website_actions = get_sub_field( $this->field_key . 'website_repeater' );
		if ( ! empty( $website_actions ) ) {
			foreach ( $website_actions as $website_action ) {

				// Try and get the Website Record.
				$website_type_id = $website_action[ $this->field_name . 'map_website_type_id' ];
				$website = $this->plugin->civicrm->website->get_by_type( $contact['id'], $website_type_id );
				if ( empty( $website ) ) {
					continue;
				}

				// Add to retrieved Website data.
				$website_record = (array) $website;
				$websites[] = $website_record;

				// Populate the Website Fields.
				foreach ( $this->website_fields as $website_field ) {
					$field = $website_action[ $this->field_name . 'map_website_' . $website_field['name'] ];
					$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
					if ( acf_is_field_key( $field ) && ! empty( $website_record[ $website_field['name'] ] ) ) {
						$form['map'][ $field ]['value'] = $website_record[ $website_field['name'] ];
					}
				}

			}
		}

		// Init retrieved Address data.
		$addresses = [];

		// Get the raw Address Actions.
		$address_actions = get_sub_field( $this->field_key . 'address_repeater' );
		if ( ! empty( $address_actions ) ) {
			foreach ( $address_actions as $address_action ) {

				// Try and get the Address Record.
				$location_type_id = $address_action[ $this->field_name . 'map_address_location_type_id' ];
				$address = (array) $this->plugin->civicrm->address->address_get_by_location( $contact['id'], $location_type_id );
				if ( empty( $address ) ) {
					continue;
				}

				// Add to retrieved Address data.
				$addresses[] = $address;

				// Populate the Address Fields.
				foreach ( $this->address_fields as $address_field ) {
					$field = $address_action[ $this->field_name . 'map_address_' . $address_field['name'] ];
					$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
					if ( acf_is_field_key( $field ) && ! empty( $address[ $address_field['name'] ] ) ) {
						$form['map'][ $field ]['value'] = $address[ $address_field['name'] ];
					}
				}

				// Handle population of Address Custom Fields.
				foreach ( $this->address_custom_fields as $key => $custom_group ) {

					// Populate the Address Custom Fields.
					foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
						$code = 'custom_' . $custom_field['id'];
						$field = $address_action[ $this->field_name . 'map_' . $code ];
						$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
						if ( acf_is_field_key( $field ) ) {
							// Allow (string) "0" as valid data.
							if ( empty( $address[ $code ] ) && $address[ $code ] !== '0' ) {
								continue;
							}
							$form['map'][ $field ]['value'] = $address[ $code ];
						}
					}

				}

			}
		}

		// Init retrieved Phone data.
		$phones = [];

		// Get the raw Phone Actions.
		$phone_actions = get_sub_field( $this->field_key . 'phone_repeater' );
		if ( ! empty( $phone_actions ) ) {
			foreach ( $phone_actions as $phone_action ) {

				// Try and get the Phone Record.
				$location_type_id = $phone_action[ $this->field_name . 'map_phone_location_type_id' ];
				$phone_type_id = $phone_action[ $this->field_name . 'map_phone_type_id' ];
				$phone_records = $this->plugin->civicrm->phone->phones_get_by_type( $contact['id'], $location_type_id, $phone_type_id );

				// We can only handle exactly one, though CiviCRM allows many.
				if ( empty( $phone_records ) || count( $phone_records ) > 1 ) {
					continue;
				}

				// Add to retrieved Phone data.
				$phone_record = (array) array_pop( $phone_records );
				$phones[] = $phone_record;

				// Populate the Phone Fields.
				foreach ( $this->phone_fields as $phone_field ) {
					$field = $phone_action[ $this->field_name . 'map_phone_' . $phone_field['name'] ];
					$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
					if ( acf_is_field_key( $field ) && ! empty( $phone_record[ $phone_field['name'] ] ) ) {
						$form['map'][ $field ]['value'] = $phone_record[ $phone_field['name'] ];
					}
				}

			}
		}

		// Init retrieved Instant Messenger data.
		$ims = [];

		// Get the raw Instant Messenger Actions.
		$im_actions = get_sub_field( $this->field_key . 'im_repeater' );
		if ( ! empty( $im_actions ) ) {
			foreach ( $im_actions as $im_action ) {

				// Try and get the Instant Messenger Record.
				$location_type_id = $im_action[ $this->field_name . 'map_im_location_type_id' ];
				$provider_id = $im_action[ $this->field_name . 'map_provider_id' ];
				$im_records = $this->civicrm->im->ims_get_by_type( $contact['id'], $location_type_id, $provider_id );

				// We can only handle exactly one, though CiviCRM allows many.
				if ( empty( $im_records ) || count( $im_records ) > 1 ) {
					continue;
				}

				// Add to retrieved Instant Messenger data.
				$im_record = (array) array_pop( $im_records );
				$ims[] = $im_record;

				// Populate the Instant Messenger Fields.
				foreach ( $this->im_fields as $im_field ) {
					$field = $im_action[ $this->field_name . 'map_im_' . $im_field['name'] ];
					$field = acfe_form_map_field_value_load( $field, $current_post_id, $form );
					if ( acf_is_field_key( $field ) && ! empty( $im_record[ $im_field['name'] ] ) ) {
						$form['map'][ $field ]['value'] = $im_record[ $im_field['name'] ];
					}
				}

			}
		}

		// Make an array of the retrieved data.
		$args = [
			'form_action' => $this->action_name,
			'contact' => $contact,
			'emails' => $emails,
			'relationships' => $relationships,
			'websites' => $websites,
			'addresses' => $addresses,
			'phones' => $phones,
			'ims' => $ims,
		];

		// Save the results of this Action for later use.
		$this->load_action_save( $action, $args );

		// --<
		return $form;

	}



	/**
	 * Performs validation when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function validation( $form, $current_post_id, $action ) {

		// Validate the Contact data.
		$valid = $this->form_contact_validate( $form, $current_post_id, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Contact Entities.

		/*
		acfe_add_validation_error( $selector, $message );
		*/

	}



	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {

		// Bail if a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Init array to save for this Action.
		$args = [
			'form_action' => $this->action_name,
			'id' => false,
		];

		// Populate Contact, Email, Relationship and Custom Field data arrays.
		$contact = $this->form_contact_data( $form, $current_post_id, $action );
		$contact_custom_fields = $this->form_custom_data( $form, $current_post_id, $action );
		$emails = $this->form_email_data( $form, $current_post_id, $action );
		$relationships = $this->form_relationship_data( $form, $current_post_id, $action );

		// Save the Contact with the data from the Form.
		$args['contact'] = $this->form_contact_save( $contact, $emails, $relationships, $contact_custom_fields );

		// If we get a Contact.
		if ( $args['contact'] !== false ) {

			// Post-process Custom Fields now that we have a Contact.
			$this->form_custom_post_process( $form, $current_post_id, $action, $args['contact'] );

			// Save the Email(s) and Relationship(s).
			$args['emails'] = $this->form_email_save( $args['contact'], $emails );
			$args['relationships'] = $this->form_relationship_save( $args['contact'], $relationships );

			// Save the Address(es) with the data from the Form.
			$addresses = $this->form_address_data( $form, $current_post_id, $action );
			$args['addresses'] = $this->form_address_save( $args['contact'], $addresses );

			// Save the Website(s) with the data from the Form.
			$websites = $this->form_website_data( $form, $current_post_id, $action );
			$args['websites'] = $this->form_website_save( $args['contact'], $websites );

			// Save the Phone(s) with the data from the Form.
			$phones = $this->form_phone_data( $form, $current_post_id, $action );
			$args['phones'] = $this->form_phone_save( $args['contact'], $phones );

			// Save the Instant Messenger(s) with the data from the Form.
			$ims = $this->form_im_data( $form, $current_post_id, $action );
			$args['ims'] = $this->form_im_save( $args['contact'], $ims );

			// Add Note(s) with the data from the Form.
			$notes = $this->form_note_data( $form, $current_post_id, $action );
			$args['notes'] = $this->form_note_save( $args['contact'], $notes );

			// Add Tag(s) with the data from the Form.
			$tags = $this->form_tag_data( $form, $current_post_id, $action );
			$args['tags'] = $this->form_tag_save( $args['contact'], $tags );

			// Add the Contact to the Group(s) with the data from the Form.
			$groups = $this->form_group_data( $form, $current_post_id, $action );
			$args['groups'] = $this->form_group_save( $args['contact'], $groups );

			// Add the Free Membership(s) to the Contact with the data from the Form.
			$args['memberships'] = [];
			if ( ! empty( $this->membership_types ) ) {
				$memberships = $this->form_membership_data( $form, $current_post_id, $action );
				$args['memberships'] = $this->form_membership_save( $args['contact'], $memberships );
			}

		} else {

			// Save an array for the Contact in case of access.
			$args['contact'] = [ 'id' => false ];

		}

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {

		// Define "Submitting Contact" Field.
		$submitting_contact_field = [
			'key' => $this->field_key . 'submitting_contact',
			'label' => __( 'Submitter', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'submitting_contact',
			'type' => 'true_false',
			'instructions' => __( 'Is this Action for the Contact who is submitting the Form?', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'message' => '',
			'default_value' => 0,
			'ui' => 1,
			'ui_on_text' => '',
			'ui_off_text' => '',
		];

		// Add top-level Contact Types.
		$contact_types = $this->civicrm->contact_type->choices_top_level_get();

		// Define Field.
		$contact_types_field = [
			'key' => $this->field_key . 'contact_types',
			'label' => __( 'Contact Type', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'contact_types',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $contact_types,
		];

		// Add Contact Sub-Types.
		$contact_sub_types = $this->civicrm->contact_type->choices_sub_types_get();

		// Define Field.
		$contact_sub_types_field = [
			'key' => $this->field_key . 'contact_sub_types',
			'label' => __( 'Contact Sub Type', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'contact_sub_types',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $contact_sub_types,
		];

		// Get all Dedupe Rules.
		$dedupe_rules = $this->civicrm->contact->dedupe_rules_get();

		// Define Field.
		$dedupe_rule_field = [
			'key' => $this->field_key . 'dedupe_rules',
			'label' => __( 'Dedupe Rule', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'dedupe_rules',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $dedupe_rules,
		];

		// Add Contact Entities Field.
		$contact_entities_field = [
			'key' => $this->field_key . 'contact_entities',
			'label' => __( 'Contact Entities', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'contact_entities',
			'type' => 'checkbox',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'allow_custom' => 0,
			'default_value' => [],
			'layout' => 'vertical',
			'toggle' => 0,
			'return_format' => 'value',
			'choices' => [
				1 => __( 'Email', 'civicrm-wp-profile-sync' ),
				2 => __( 'Website', 'civicrm-wp-profile-sync' ),
				3 => __( 'Address', 'civicrm-wp-profile-sync' ),
				4 => __( 'Phone', 'civicrm-wp-profile-sync' ),
				5 => __( 'Instant Messenger', 'civicrm-wp-profile-sync' ),
				7 => __( 'Note', 'civicrm-wp-profile-sync' ),
				8 => __( 'Tag', 'civicrm-wp-profile-sync' ),
				6 => __( 'Group', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add Membership option if there are Free Memberships.
		if ( ! empty( $this->membership_types ) ) {
			$contact_entities_field['choices'][9] = __( 'Free Membership', 'civicrm-wp-profile-sync' );
		}

		// Init Fields.
		$fields = [
			$submitting_contact_field,
			$contact_types_field,
			$contact_sub_types_field,
			$dedupe_rule_field,
			$contact_entities_field,
		];

		// Add Conditional Field.
		$code = 'contact_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions'] = __( 'To add the Contact only when a Form Field is populated (e.g. "First Name") link this to the Form Field. To add the Contact only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
		$fields[] = $conditional;

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// "Auto-fill Enabled" Field.
		$autoload_enabled = [
			[
				'key' => $this->field_key . 'autoload_enabled',
				'label' => __( 'Auto-fill with data from CiviCRM', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'autoload_enabled',
				'type' => 'true_false',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field',
				],
				'acfe_permissions' => '',
				'message' => '',
				'default_value' => 0,
				'ui' => 1,
				'ui_on_text' => '',
				'ui_off_text' => '',
			],
		];

		// Build Contact Details Accordion.
		$mapping_contact_accordion = $this->tab_mapping_accordion_contact_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Build Email Accordion.
		$mapping_email_accordion = $this->tab_mapping_accordion_email_add();

		// Build Website Accordion.
		$mapping_website_accordion = $this->tab_mapping_accordion_website_add();

		// Build Address Accordion.
		$mapping_address_accordion = $this->tab_mapping_accordion_address_add();

		// Build Phone Accordion.
		$mapping_phone_accordion = $this->tab_mapping_accordion_phone_add();

		// Build Instant Messenger Accordion.
		$mapping_im_accordion = $this->tab_mapping_accordion_im_add();

		// Build Note Accordion.
		$mapping_note_accordion = $this->tab_mapping_accordion_note_add();

		// Build Tag Accordion.
		$mapping_tag_accordion = $this->tab_mapping_accordion_tag_add();

		// Build Group Accordion.
		$mapping_group_accordion = $this->tab_mapping_accordion_group_add();

		// Build Free Membership Accordion if there are some.
		$mapping_membership_accordion = [];
		if ( ! empty( $this->membership_types ) ) {
			$mapping_membership_accordion = $this->tab_mapping_accordion_membership_add();
		}

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$autoload_enabled,
			$mapping_contact_accordion,
			$mapping_custom_accordion,
			$mapping_email_accordion,
			$mapping_website_accordion,
			$mapping_address_accordion,
			$mapping_phone_accordion,
			$mapping_im_accordion,
			$mapping_note_accordion,
			$mapping_tag_accordion,
			$mapping_group_accordion,
			$mapping_membership_accordion
		);

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Contact Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_contact_add() {

		// Init return.
		$fields = [];

		// "Contact Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contact_open',
			'label' => __( 'Contact Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->public_contact_fields as $contact_type => $fields_for_type ) {
			foreach ( $fields_for_type as $field ) {

				// Common Fields do not need extra conditional logic.
				$conditional_logic = [];
				if ( $contact_type !== 'common' ) {

					// Custom conditional logic.
					$conditional_logic = [
						[
							[
								'field' => $this->field_key . 'contact_types',
								'operator' => '==contains',
								'value' => $contact_type,
							],
						],
					];

				}

				// Add "Map" Field.
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'], $conditional_logic );

			}
		}

		// "Contact Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contact_close',
			'label' => __( 'Contact Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Custom Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_custom_add() {

		// Init return.
		$fields = [];

		// "Custom Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_open',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Get top-level Contact Types.
		$contact_types = $this->civicrm->contact_type->choices_top_level_get();

		// Add Contact Sub-Types.
		$contact_sub_types = $this->civicrm->contact_type->choices_sub_types_get();

		// Add "Mapping" Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Get the Contact Type ID.
			$contact_type_id = array_search( $custom_group['extends'], $contact_types );

			// Get the Contact Sub-type IDs.
			$contact_sub_type_ids = [];
			if ( ! empty( $custom_group['extends_entity_column_value'] ) ) {
				foreach ( $custom_group['extends_entity_column_value'] as $sub_type ) {
					$contact_sub_type_ids[] = array_search( $sub_type, $contact_sub_types[ $custom_group['extends'] ] );
				}
			}

			// Init conditional logic.
			$conditional_logic = [];

			// Top-level always needed.
			$top_level = [
				'field' => $this->field_key . 'contact_types',
				'operator' => '==contains',
				'value' => $contact_type_id,
			];

			// Add Sub-types as OR conditionals if present.
			if ( ! empty( $contact_sub_type_ids ) ) {
				foreach ( $contact_sub_type_ids as $contact_sub_type_id ) {

					$sub_type = [
						'field' => $this->field_key . 'contact_sub_types',
						'operator' => '==contains',
						'value' => $contact_sub_type_id,
					];

					$conditional_logic[] = [
						$top_level,
						$sub_type,
					];

				}
			} else {
				$conditional_logic = [
					[
						$top_level,
					],
				];
			}

			// Bundle the Custom Fields into a container group.
			$custom_group_field = [
				'key' => $this->field_key . 'custom_group_' . $custom_group['id'],
				'label' => $custom_group['title'],
				'name' => $this->field_name . 'custom_group_' . $custom_group['id'],
				'type' => 'group',
				'instructions' => '',
				'instruction_placement' => 'field',
				'required' => 0,
				'layout' => 'block',
				'conditional_logic' => $conditional_logic,
			];

			// Init sub Fields array.
			$sub_fields = [];

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'], $conditional_logic );
			}

			// Add the Sub-fields.
			$custom_group_field['sub_fields'] = $sub_fields;

			// Add the Sub-fields.
			$fields[] = $custom_group_field;

		}

		// "Custom Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_close',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Email" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_email_add() {

		// Init return.
		$fields = [];

		// "Email" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_email_open',
			'label' => __( 'Email', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 1, // Email ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Email Repeater Field.
		$email_repeater = [
			'key' => $this->field_key . 'email_repeater',
			'label' => __( 'Email Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'email_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_email_location_type_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Email action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'email_location_type_id';
		$label = __( 'Location Type', 'civicrm-wp-profile-sync' );

		// Get Email Location Type "Mapping" Field.
		$email_location_type_field = $this->mapping_field_get( $code, $label );

		// Build Location Types choices array for dropdown.
		$choices = [];
		foreach ( $this->location_types as $location_type ) {
			$choices[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Add choices and modify Field.
		$email_location_type_field['choices'] = $choices;
		$email_location_type_field['search_placeholder'] = '';
		$email_location_type_field['allow_null'] = 0;
		$email_location_type_field['ui'] = 0;
		$email_location_type_field['default_value'] = $this->location_type_default;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $email_location_type_field;

		// ---------------------------------------------------------------------

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->email_fields as $email_field ) {
			$sub_fields[] = $this->mapping_field_get( 'email_' . $email_field['name'], $email_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'email_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$email_conditional = $this->mapping_field_get( $code, $label );
		$email_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$email_conditional['instructions'] = __( 'To add the Email to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $email_conditional;

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$email_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $email_repeater;

		// "Email" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_email_close',
			'label' => __( 'Email', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 1, // Email ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Website" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_website_add() {

		// Init return.
		$fields = [];

		// "Website" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_website_open',
			'label' => __( 'Website', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 2, // Website ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Website Repeater Field.
		$website_repeater = [
			'key' => $this->field_key . 'website_repeater',
			'label' => __( 'Website Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'website_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_website_type_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Website action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'website_type_id';
		$label = __( 'Website Type', 'civicrm-wp-profile-sync' );

		// Get Website Type "Mapping" Field.
		$website_type_field = $this->mapping_field_get( $code, $label );

		// Add Website Types choices and modify Field.
		$website_type_field['choices'] = $this->website_types;
		$website_type_field['default_value'] = $this->civicrm->option_value_default_get( 'website_type' );
		$website_type_field['search_placeholder'] = '';
		$website_type_field['allow_null'] = 0;
		$website_type_field['ui'] = 0;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $website_type_field;

		// ---------------------------------------------------------------------

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->website_fields as $website_field ) {
			$sub_fields[] = $this->mapping_field_get( 'website_' . $website_field['name'], $website_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'website_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$website_conditional = $this->mapping_field_get( $code, $label );
		$website_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$website_conditional['instructions'] = __( 'To add the Website to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $website_conditional;

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$website_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $website_repeater;

		// "Website" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_website_close',
			'label' => __( 'Website', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 2, // Website ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Address" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_address_add() {

		// Init return.
		$fields = [];

		// "Address" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_address_open',
			'label' => __( 'Address', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 3, // Address ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Address Repeater Field.
		$address_repeater = [
			'key' => $this->field_key . 'address_repeater',
			'label' => __( 'Address Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'address_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_address_location_type_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Address action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'address_location_type_id';
		$label = __( 'Location Type', 'civicrm-wp-profile-sync' );

		// Get Address Location Type "Mapping" Field.
		$address_location_type_field = $this->mapping_field_get( $code, $label );

		// Build Location Types choices array for dropdown.
		$choices = [];
		foreach ( $this->location_types as $location_type ) {
			$choices[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Add choices and modify Field.
		$address_location_type_field['choices'] = $choices;
		$address_location_type_field['search_placeholder'] = '';
		$address_location_type_field['allow_null'] = 0;
		$address_location_type_field['ui'] = 0;
		$address_location_type_field['default_value'] = $this->location_type_default;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $address_location_type_field;

		// ---------------------------------------------------------------------

		// "Include empty Fields" Field.
		$sub_fields[] = [
			'key' => $this->field_key . 'is_override',
			'label' => __( 'Include empty Fields', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'is_override',
			'type' => 'true_false',
			'instructions' => __( 'Enable this to include empty Fields in the data that is sent to CiviCRM. This will cause the value to be cleared.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'message' => '',
			'default_value' => 0,
			'ui' => 1,
			'ui_on_text' => '',
			'ui_off_text' => '',
		];

		// Maybe open Accordion.
		if ( ! empty( $this->address_custom_fields ) ) {

			// "Address Fields" Accordion wrapper open.
			$sub_fields[] = [
				'key' => $this->field_key . 'address_fields_open',
				'label' => __( 'Address Fields', 'civicrm-wp-profile-sync' ),
				'name' => '',
				'type' => 'accordion',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'open' => 0,
				'multi_expand' => 1,
				'endpoint' => 0,
			];

		}

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->address_fields as $address_field ) {
			$sub_fields[] = $this->mapping_field_get( 'address_' . $address_field['name'], $address_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'address_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$address_conditional = $this->mapping_field_get( $code, $label );
		$address_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$address_conditional['instructions'] = __( 'To add the Address to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $address_conditional;

		// ---------------------------------------------------------------------

		// Maybe close Accordion.
		if ( ! empty( $this->address_custom_fields ) ) {

			// "Address Fields" Accordion wrapper close.
			$sub_fields[] = [
				'key' => $this->field_key . 'address_fields_close',
				'label' => __( 'Address Fields', 'civicrm-wp-profile-sync' ),
				'name' => '',
				'type' => 'accordion',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'open' => 0,
				'multi_expand' => 1,
				'endpoint' => 1,
			];

		}

		// Maybe add Custom Fields Accordion to Sub-fields.
		if ( ! empty( $this->address_custom_fields ) ) {
			$custom_fields = $this->tab_mapping_accordion_address_custom_add();
			$sub_fields = array_merge( $sub_fields, $custom_fields );
		}

		// Add to Repeater.
		$address_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $address_repeater;

		// "Address" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_address_close',
			'label' => __( 'Address', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 3, // Address ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Address Custom Fields" Accordion.
	 *
	 * @since 0.5.1
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_address_custom_add() {

		// Init return.
		$fields = [];

		// Skip if there are none.
		if ( empty( $this->address_custom_fields ) ) {
			return $fields;
		}

		// "Custom Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'address_custom_open',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->address_custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$fields[] = $this->mapping_field_get( $code, $custom_field['label'] );
			}

		}

		// "Custom Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'address_custom_close',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Phone" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_phone_add() {

		// Init return.
		$fields = [];

		// "Phone" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_phone_open',
			'label' => __( 'Phone', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 4, // Phone ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Phone Repeater Field.
		$phone_repeater = [
			'key' => $this->field_key . 'phone_repeater',
			'label' => __( 'Phone Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'phone_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_phone_type_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Phone action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'phone_type_id';
		$label = __( 'Phone Type', 'civicrm-wp-profile-sync' );

		// Get Phone Type "Mapping" Field.
		$phone_type_field = $this->mapping_field_get( $code, $label );

		// Add Phone Types choices and modify Field.
		$phone_type_field['choices'] = $this->phone_types;
		$phone_type_field['search_placeholder'] = '';
		$phone_type_field['allow_null'] = 0;
		$phone_type_field['ui'] = 0;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $phone_type_field;

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'phone_location_type_id';
		$label = __( 'Location Type', 'civicrm-wp-profile-sync' );

		// Get Phone Location Type "Mapping" Field.
		$phone_location_type_field = $this->mapping_field_get( $code, $label );

		// Build Location Types choices array for dropdown.
		$choices = [];
		foreach ( $this->location_types as $location_type ) {
			$choices[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Add choices and modify Field.
		$phone_location_type_field['choices'] = $choices;
		$phone_location_type_field['search_placeholder'] = '';
		$phone_location_type_field['allow_null'] = 0;
		$phone_location_type_field['ui'] = 0;
		$phone_location_type_field['default_value'] = $this->location_type_default;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $phone_location_type_field;

		// ---------------------------------------------------------------------

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->phone_fields as $phone_field ) {
			$sub_fields[] = $this->mapping_field_get( 'phone_' . $phone_field['name'], $phone_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'phone_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$phone_conditional = $this->mapping_field_get( $code, $label );
		$phone_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$phone_conditional['instructions'] = __( 'To add the Phone to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $phone_conditional;

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$phone_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $phone_repeater;

		// "Phone" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_phone_close',
			'label' => __( 'Phone', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 4, // Phone ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Instant Messenger" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_im_add() {

		// Init return.
		$fields = [];

		// "Instant Messenger" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_im_open',
			'label' => __( 'Instant Messenger', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 5, // Instant Messenger ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Instant Messenger Repeater Field.
		$im_repeater = [
			'key' => $this->field_key . 'im_repeater',
			'label' => __( 'Instant Messenger Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'im_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_provider_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Instant Messenger action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'provider_id';
		$label = __( 'Instant Messenger Type', 'civicrm-wp-profile-sync' );

		// Get Instant Messenger Provider "Mapping" Field.
		$im_type_field = $this->mapping_field_get( $code, $label );

		// Add Instant Messenger Providers choices and modify Field.
		$im_type_field['choices'] = $this->im_providers;
		$im_type_field['search_placeholder'] = '';
		$im_type_field['allow_null'] = 0;
		$im_type_field['ui'] = 0;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $im_type_field;

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'im_location_type_id';
		$label = __( 'Location Type', 'civicrm-wp-profile-sync' );

		// Get Instant Messenger Location Type "Mapping" Field.
		$im_location_type_field = $this->mapping_field_get( $code, $label );

		// Build Location Types choices array for dropdown.
		$choices = [];
		foreach ( $this->location_types as $location_type ) {
			$choices[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Add choices and modify Field.
		$im_location_type_field['choices'] = $choices;
		$im_location_type_field['search_placeholder'] = '';
		$im_location_type_field['allow_null'] = 0;
		$im_location_type_field['ui'] = 0;
		$im_location_type_field['default_value'] = $this->location_type_default;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $im_location_type_field;

		// ---------------------------------------------------------------------

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->im_fields as $im_field ) {
			$sub_fields[] = $this->mapping_field_get( 'im_' . $im_field['name'], $im_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'im_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$im_conditional = $this->mapping_field_get( $code, $label );
		$im_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$im_conditional['instructions'] = __( 'To add the Instant Messenger to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $im_conditional;

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$im_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $im_repeater;

		// "Instant Messenger" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_im_close',
			'label' => __( 'Instant Messenger', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 5, // Instant Messenger ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Group" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_group_add() {

		// Init return.
		$fields = [];

		// "Group" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_group_open',
			'label' => __( 'Group', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 6, // Group ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Group Repeater Field.
		$group_repeater = [
			'key' => $this->field_key . 'group_repeater',
			'label' => __( 'Group Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'group_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_group_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Group action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'group_id';
		$label = __( 'Add To Group', 'civicrm-wp-profile-sync' );

		// Get Group Type "Mapping" Field.
		$group_field = $this->mapping_field_get( $code, $label );

		// Get all Groups from CiviCRM.
		$groups_all = $this->civicrm->group->groups_get_all();
		$choices = [];
		foreach ( $groups_all as $group ) {
			$choices[ $group['id'] ] = $group['title'];
		}

		// Add Group choices and modify Field.
		$group_field['choices'] = $choices;
		$group_field['search_placeholder'] = '';
		$group_field['allow_null'] = 0;
		$group_field['ui'] = 0;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $group_field;

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'group_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$group_conditional = $this->mapping_field_get( $code, $label );
		$group_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$group_conditional['instructions'] = __( 'To add the Contact to the Group only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $group_conditional;

		// ---------------------------------------------------------------------

		// "Enable double opt-in" Field.
		$sub_fields[] = [
			'key' => $this->field_key . 'double_optin',
			'label' => __( 'Enable double opt-in?', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'double_optin',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'message' => '',
			'default_value' => 0,
			'ui' => 1,
			'ui_on_text' => '',
			'ui_off_text' => '',
		];

		// Add to Repeater.
		$group_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $group_repeater;

		// "Group" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_group_close',
			'label' => __( 'Group', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 6, // Group ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Membership" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_membership_add() {

		// Init return.
		$fields = [];

		// "Free Membership" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_membership_open',
			'label' => __( 'Free Membership', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 9, // Membership ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Membership Repeater Field.
		$membership_repeater = [
			'key' => $this->field_key . 'membership_repeater',
			'label' => __( 'Membership Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'membership_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_membership_type_id',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Membership action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'membership_type_id';
		$label = __( 'Add Free Membership', 'civicrm-wp-profile-sync' );

		// Get Membership Type "Mapping" Field.
		$membership_type_field = $this->mapping_field_get( $code, $label );

		// Get all Free Membership Types from CiviCRM.
		$choices = [];
		foreach ( $this->membership_types as $membership_type ) {
			$choices[ $membership_type['id'] ] = $membership_type['name'];
		}

		// Add Membership choices and modify Field.
		$membership_type_field['choices'] = $choices;
		$membership_type_field['search_placeholder'] = '';
		$membership_type_field['allow_null'] = 0;
		$membership_type_field['ui'] = 0;

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $membership_type_field;

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'membership_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$membership_conditional = $this->mapping_field_get( $code, $label );
		$membership_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$membership_conditional['instructions'] = __( 'To add the Free Membership to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $membership_conditional;

		// ---------------------------------------------------------------------

		// Add Campaign Field if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {

			$sub_fields[] = [
				'key' => $this->field_key . 'membership_campaign_id',
				'label' => __( 'Campaign', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'membership_campaign_id',
				'type' => 'select',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => '',
				'allow_null' => 1,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => $this->civicrm->campaign->choices_get(),
			];

		}

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->membership_fields as $membership_field ) {
			$sub_fields[] = $this->mapping_field_get( 'membership_' . $membership_field['name'], $membership_field['title'] );
		}

		// Add to Repeater.
		$membership_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $membership_repeater;

		// "Free Membership" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_membership_close',
			'label' => __( 'Free Membership', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 9, // Membership ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Note" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_note_add() {

		// Init return.
		$fields = [];

		// "Note" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_note_open',
			'label' => __( 'Note', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 7, // The Note ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Note Repeater Field.
		$note_repeater = [
			'key' => $this->field_key . 'note_repeater',
			'label' => __( 'Note Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'note_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_note_subject',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Note action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->note_fields as $note_field ) {
			$sub_fields[] = $this->mapping_field_get( 'note_' . $note_field['name'], $note_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'note_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$note_conditional = $this->mapping_field_get( $code, $label );
		$note_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$note_conditional['instructions'] = __( 'To add the Note to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $note_conditional;

		// ---------------------------------------------------------------------

		$attachment_fields = $this->tab_mapping_accordion_attachment_add();
		$sub_fields = array_merge( $sub_fields, $attachment_fields );

		// Add to Repeater.
		$note_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $note_repeater;

		// "Note" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_note_close',
			'label' => __( 'Note', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 7, // The Note ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Attachment(s)" Accordion for "Note" Actions.
	 *
	 * @since 0.5.2
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_attachment_add() {

		// Init return.
		$fields = [];

		// "Attachment" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_attachment_open',
			'label' => __( 'Attachment(s)', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Attachment Repeater Field.
		$attachment_repeater = [
			'key' => $this->field_key . 'attachment_repeater',
			'label' => __( 'Attachment Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'attachment_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_attachment_file',
			'min' => 0,
			'max' => 3,
			'layout' => 'block',
			'button_label' => __( 'Add Attachment action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// First add "File" Field to Repeater's Sub-Fields.
		$code = 'attachment_file';
		$label = __( 'File', 'civicrm-wp-profile-sync' );
		$file = $this->mapping_field_get( $code, $label );
		$sub_fields[] = $file;

		// ---------------------------------------------------------------------

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->attachment_fields as $attachment_field ) {
			$sub_fields[] = $this->mapping_field_get( 'attachment_' . $attachment_field['name'], $attachment_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Assign code and label for "Conditional" Field.
		$code = 'attachment_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$attachment_conditional = $this->mapping_field_get( $code, $label );
		$attachment_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$attachment_conditional['instructions'] = __( 'To add the Attachment to the Note only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $attachment_conditional;

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$attachment_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $attachment_repeater;

		// "Attachment" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_attachment_close',
			'label' => __( 'Attachment', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the "Tag" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_tag_add() {

		// Init return.
		$fields = [];

		// "Tag" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_tag_open',
			'label' => __( 'Tag', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 8, // Tag ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Define the Tag Repeater Field.
		$tag_repeater = [
			'key' => $this->field_key . 'tag_repeater',
			'label' => __( 'Tag Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'tag_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'contact_tags',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Tag action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// Get all Tags from CiviCRM.
		$tags = $this->civicrm->tag->get_for_contacts();

		$choices = [];
		foreach ( $tags as $tag ) {
			$choices[ $tag['id'] ] = esc_html( $tag['name'] );
		}

		// Add Tags Field.
		$sub_fields[] = [
			'key' => $this->field_key . 'contact_tags',
			'label' => __( 'Add Tag(s) to Contact', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'contact_tags',
			'type' => 'checkbox',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'allow_custom' => 0,
			'default_value' => [],
			'layout' => 'vertical',
			'toggle' => 0,
			'return_format' => 'value',
			'choices' => $choices,
		];

		// ---------------------------------------------------------------------

		// Assign code and label.
		$code = 'tag_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$tag_conditional = $this->mapping_field_get( $code, $label );
		$tag_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$tag_conditional['instructions'] = __( 'To add the Tag(s) to the Contact only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $tag_conditional;

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$tag_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $tag_repeater;

		// "Tag" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_tag_close',
			'label' => __( 'Tag', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'contact_entities',
						'operator' => '==contains',
						'value' => 8, // Tag ID.
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines "Relationship" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_relationship_add() {

		// Get Tab Header.
		$relationship_tab_header = $this->tab_relationship_header();

		// Build Relationship Accordion.
		$relationship_accordion = $this->tab_relationship_accordion_relationship_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$relationship_tab_header,
			$relationship_accordion
		);

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Relationship Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_relationship_accordion_relationship_add() {

		// Init return.
		$fields = [];

		// Define the Relationship Repeater Field.
		$relationship_repeater = [
			'key' => $this->field_key . 'relationship_repeater',
			'label' => __( 'Relationship Actions', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'relationship_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'relationship_action_ref',
			//'collapsed' => $this->field_key . 'relationship_type',
			'min' => 0,
			'max' => 0,
			'layout' => 'block',
			'button_label' => __( 'Add Relationship action', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// Define Action Reference Field.
		$sub_fields[] = [
			'key' => $this->field_key . 'relationship_action_ref',
			'label' => __( 'Related Contact', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'relationship_action_ref',
			'type' => 'cwps_acfe_contact_action_ref',
			'instructions' => __( 'Is this Contact related to another Contact?', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'submitting_contact',
						'operator' => '==',
						'value' => '0',
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => __( 'Not related', 'civicrm-wp-profile-sync' ),
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => [],
		];

		// Maybe open Accordion.
		if ( ! empty( $this->relationship_custom_fields ) ) {

			// "Relationship Fields" Accordion wrapper open.
			$sub_fields[] = [
				'key' => $this->field_key . 'relationship_fields_open',
				'label' => __( 'Relationship Fields', 'civicrm-wp-profile-sync' ),
				'name' => '',
				'type' => 'accordion',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'open' => 0,
				'multi_expand' => 1,
				'endpoint' => 0,
			];

		}

		// Define Relationship Types Field.
		$sub_fields[] = [
			'key' => $this->field_key . 'relationship_type',
			'label' => __( 'Relationship', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'relationship_type',
			'type' => 'select',
			'instructions' => __( 'Select the relationship this Contact has with the related Contact', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'relationship_action_ref',
						'operator' => '!=empty',
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->relationship_choices,
		];

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->relationship_fields as $field ) {

			// Custom conditional logic.
			$conditional_logic = [
				[
					[
						'field' => $this->field_key . 'relationship_action_ref',
						'operator' => '!=empty',
					],
				],
			];

			// Add "Mapping" Field.
			$sub_fields[] = $this->mapping_field_get( $field['name'], $field['title'], $conditional_logic );

		}

		// Maybe close Accordion.
		if ( ! empty( $this->relationship_custom_fields ) ) {

			// "Relationship Fields" Accordion wrapper close.
			$sub_fields[] = [
				'key' => $this->field_key . 'relationship_fields_close',
				'label' => __( 'Relationship Fields', 'civicrm-wp-profile-sync' ),
				'name' => '',
				'type' => 'accordion',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'open' => 0,
				'multi_expand' => 1,
				'endpoint' => 1,
			];

		}

		// Maybe add Custom Fields Accordion to Sub-fields.
		$custom_fields = $this->tab_relationship_accordion_custom_add();
		if ( ! empty( $custom_fields ) ) {
			$sub_fields = array_merge( $sub_fields, $custom_fields );
		}

		// Add Sub-fields to Repeater.
		$relationship_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $relationship_repeater;

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Relationship Custom Fields" Accordion.
	 *
	 * @since 0.5.1
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_relationship_accordion_custom_add() {

		// Init return.
		$fields = [];

		// Skip if there are none.
		if ( empty( $this->relationship_custom_fields ) ) {
			return $fields;
		}

		// "Custom Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'relationship_custom_open',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->relationship_custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Get the Relationship Type IDs.
			$relationship_type_ids = [];
			if ( ! empty( $custom_group['extends_entity_column_value'] ) ) {
				$relationship_type_ids = $custom_group['extends_entity_column_value'];
			}

			// Init conditional logic.
			$conditional_logic = [];

			// Add Types as OR conditionals if present.
			if ( ! empty( $relationship_type_ids ) ) {
				foreach ( $relationship_type_ids as $relationship_type_id ) {

					$relationship_type_ab = [
						'field' => $this->field_key . 'relationship_type',
						'operator' => '==contains',
						'value' => $relationship_type_id . '_ab',
					];

					$conditional_logic[] = [
						$relationship_type_ab,
					];

					$relationship_type_ba = [
						'field' => $this->field_key . 'relationship_type',
						'operator' => '==contains',
						'value' => $relationship_type_id . '_ba',
					];

					$conditional_logic[] = [
						$relationship_type_ba,
					];

					$relationship_type_equal = [
						'field' => $this->field_key . 'relationship_type',
						'operator' => '==contains',
						'value' => $relationship_type_id . '_equal',
					];

					$conditional_logic[] = [
						$relationship_type_equal,
					];

				}
			}

			// Bundle the Custom Fields into a container group.
			$custom_group_field = [
				'key' => $this->field_key . 'relationship_custom_group_' . $custom_group['id'],
				'label' => $custom_group['title'],
				'name' => $this->field_name . 'relationship_custom_group_' . $custom_group['id'],
				'type' => 'group',
				'instructions' => '',
				'instruction_placement' => 'field',
				'required' => 0,
				'layout' => 'block',
				'conditional_logic' => $conditional_logic,
			];

			// Init sub Fields array.
			$sub_fields = [];

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'], $conditional_logic );
			}

			// Add the Sub-fields.
			$custom_group_field['sub_fields'] = $sub_fields;

			// Add the Sub-fields.
			$fields[] = $custom_group_field;

		}

		// "Custom Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'relationship_custom_close',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Contact data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Contact data.
	 */
	public function form_contact_data( $form, $current_post_id, $action ) {

		// Build Fields array.
		$fields = [];
		foreach ( $this->public_contact_fields as $contact_type => $fields_for_type ) {
			foreach ( $fields_for_type as $field ) {
				$fields[ $field['name'] ] = get_sub_field( $this->field_key . 'map_' . $field['name'] );
			}
		}

		// Populate data array with values of mapped Fields.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get the Contact Type.
		$contact_type_id = get_sub_field( $this->field_key . 'contact_types' );
		$contact_type = $this->plugin->civicrm->contact_type->get_data( $contact_type_id, 'id' );
		if ( ! empty( $contact_type ) ) {
			$data['contact_type'] = $contact_type['name'];
		}

		// Get the Contact Sub-type.
		$contact_sub_type_id = get_sub_field( $this->field_key . 'contact_sub_types' );
		$contact_sub_type = $this->plugin->civicrm->contact_type->get_data( $contact_sub_type_id, 'id' );
		if ( ! empty( $contact_sub_type ) ) {
			$data['contact_sub_type'] = $contact_sub_type['name'];
		}

		// Get Contact Conditional Reference.
		$data['contact_conditional_ref'] = get_sub_field( $this->field_key . 'map_contact_conditional' );
		$conditionals = [ $data['contact_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Contact Conditional.
		$data['contact_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

	}



	/**
	 * Validates the Contact data array from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Contact can be saved, false otherwise.
	 */
	public function form_contact_validate( $form, $current_post_id, $action ) {

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Get the Contact.
		$contact = $this->form_contact_data( $form, $current_post_id, $action );

		// Skip validation if the Contact Conditional Reference Field has a value.
		if ( ! empty( $contact['contact_conditional_ref'] ) ) {
			// And the Contact Conditional Field has no value.
			if ( empty( $contact['contact_conditional'] ) ) {
				return true;
			}
		}

		$emails = $this->form_email_data( $form, $current_post_id, $action );
		$relationships = $this->form_relationship_data( $form, $current_post_id, $action );

		// Get the Contact ID with the data from the Form.
		$contact_id = $this->form_contact_id_get( $contact, $emails, $relationships );

		// All's well if we get a Contact ID.
		if ( ! empty( $contact_id ) ) {
			return true;
		}

		// Check if we have the minimum data necessary to create a Contact.
		$display = false;
		if ( ! empty( $contact['display_name'] ) ) {
			$display = true;
		}
		$first_last = false;
		if ( ! empty( $contact['first_name'] ) && ! empty( $contact['last_name'] ) ) {
			$first_last = true;
		}

		// All's well if we can create the Contact with what we have.
		if ( $first_last || $display ) {
			return true;
		}

		// All's well if there is an Email to assign as the Display Name.
		$email = $this->form_email_primary_get( $emails );
		if ( ! empty( $email ) ) {
			return true;
		}

		// Reject the submission.
		acfe_add_validation_error( '', sprintf(
			/* translators: %s The name of the Form Action */
			__( 'Not enough data to save a Contact in "%s".', 'civicrm-wp-profile-sync' ),
			$action
		) );

		// Not valid.
		return false;

	}



	/**
	 * Saves the CiviCRM Contact given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_data The array of Contact data.
	 * @param array $email_data The array of Email data.
	 * @param array $relationship_data The array of Relationship data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array|bool $contact The Contact data array, or false on failure.
	 */
	public function form_contact_save( $contact_data, $email_data, $relationship_data, $custom_data ) {

		// Init return.
		$contact = false;

		// Skip if the Contact Conditional Reference Field has a value.
		if ( ! empty( $contact_data['contact_conditional_ref'] ) ) {
			// And the Contact Conditional Field has no value.
			if ( empty( $contact_data['contact_conditional'] ) ) {
				return $contact;
			}
		}

		// Get the Contact ID with the data from the Form.
		$contact_id = $this->form_contact_id_get( $contact_data, $email_data, $relationship_data );

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$contact_data += $custom_data;
		}

		// Unset Contact Conditionals.
		if ( isset( $contact_data['contact_conditional'] ) ) {
			unset( $contact_data['contact_conditional'] );
		}
		if ( isset( $contact_data['contact_conditional_ref'] ) ) {
			unset( $contact_data['contact_conditional_ref'] );
		}

		// Strip out empty Fields.
		$contact_data = $this->form_data_prepare( $contact_data );

		// Create or update depending on the presence of an ID.
		if ( $contact_id === false ) {

			/*
			 * Check if we have the minimum data necessary to create a Contact.
			 *
			 * We are mirroring the logic in the CiviCRM admin UI here such that
			 * "First Name" and "Last Name" OR an Email must be set.
			 *
			 * Unlike the CiviCRM UI, this plugin also allows a "Display Name"
			 * to be set instead.
			 *
			 * The CiviCRM UI also supports "an OpenID in the Primary Location"
			 * so this plugin should also do so in future.
			 *
			 * @see self::validation()
			 */
			$first_last = false;
			if ( ! empty( $contact_data['first_name'] ) && ! empty( $contact_data['last_name'] ) ) {
				$first_last = true;
			}
			$display = false;
			if ( ! empty( $contact_data['display_name'] ) ) {
				$display = true;
			}

			// If we can't create the Contact with what we have.
			if ( ! $first_last && ! $display ) {
				// Try and assign an Email as the Display Name.
				$email = $this->form_email_primary_get( $email_data );
				if ( ! empty( $email ) ) {
					$contact_data['display_name'] = $email;
					$display = true;
				}
			}

			// Bail if we still can't create the Contact.
			if ( ! $first_last && ! $display ) {
				return $contact;
			}

			// Okay, we should be good to create the Contact.
			$result = $this->plugin->civicrm->contact->create( $contact_data );

		} else {

			// Use the Contact ID to update.
			$contact_data['id'] = $contact_id;

			/*
			 * We need to ensure any existing Contact Sub-types are retained.
			 *
			 * However, the Contact Sub-type could be:
			 *
			 * * Empty.
			 * * The "name" of a Sub-type. (Check this.)
			 * * An array of Sub-type "names".
			 *
			 * The following handles all possibilities.
			 */
			if ( ! empty( $contact_data['contact_sub_type'] ) ) {
				$existing_contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );

				// When there is already more than one Sub-type.
				if ( is_array( $existing_contact['contact_sub_type'] ) ) {

					// Add incoming when it doesn't exist, otherwise retain existing.
					if ( ! in_array( $contact_data['contact_sub_type'], $existing_contact['contact_sub_type'] ) ) {
						$existing_contact['contact_sub_type'][] = $contact_data['contact_sub_type'];
						$contact_data['contact_sub_type'] = $existing_contact['contact_sub_type'];
					} else {
						$contact_data['contact_sub_type'] = $existing_contact['contact_sub_type'];
					}

				} else {

					// Make an array of both when the existing is different.
					if ( ! empty( $existing_contact['contact_sub_type'] ) ) {
						if ( $contact_data['contact_sub_type'] !== $existing_contact['contact_sub_type'] ) {
							$new_contact_sub_types = [ $existing_contact['contact_sub_type'] ];
							$new_contact_sub_types[] = $contact_data['contact_sub_type'];
							$contact_data['contact_sub_type'] = $new_contact_sub_types;
						}
					}

				}

			}

			// Okay, we're good to update now.
			$result = $this->plugin->civicrm->contact->update( $contact_data );

		}

		// Bail on failure.
		if ( $result === false ) {
			return $contact;
		}

		// Get the full Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $result['id'] );

		// Add to the Domain Group if necessary.
		$domain_group_id = $this->plugin->civicrm->get_setting( 'domain_group_id' );
		if ( ! empty( $domain_group_id ) && is_numeric( $domain_group_id ) ) {
			$this->civicrm->group->group_contact_create( $domain_group_id, $contact['id'] );
		}

		// --<
		return $contact;

	}



	/**
	 * Finds the Contact ID given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_data The array of Contact data.
	 * @param array $email_data The array of Email data.
	 * @param array $relationship_data The array of Relationship data.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get( $contact_data, $email_data, $relationship_data ) {

		// Init return.
		$contact_id = false;

		// Try the "Form Submitter".
		$submitter = $this->form_contact_id_get_submitter();
		if ( $submitter ) {
			return $submitter;
		}

		// Try the "Contact ID" Field.
		$from_field = $this->form_contact_id_get_from_field( $contact_data );
		if ( $from_field ) {
			return $from_field;
		}

		// Try the "Related Contact".
		$related = $this->form_contact_id_get_related( $relationship_data );
		if ( $related ) {
			return $related;
		}

		// Try the "Deduped Contact".
		$deduped = $this->form_contact_id_get_deduped( $contact_data, $email_data );
		if ( $deduped ) {
			return $deduped;
		}

		// --<
		return $contact_id;

	}



	/**
	 * Finds the Contact ID when this is the "Form Submitter".
	 *
	 * @since 0.5
	 *
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_submitter() {

		// Init return.
		$contact_id = false;

		// Check the "Form Submitter" Contact Field.
		$submitter_contact = get_sub_field( $this->field_key . 'submitting_contact' );

		// Bail if not Submitting Contact Action.
		if ( ! $submitter_contact ) {
			return $contact_id;
		}

		// First look for a Contact specified by a checksum.
		$contact_id = $this->civicrm->contact->get_id_by_checksum();

		// If there is a logged-in User, prefer their details.
		if ( is_user_logged_in() ) {
			$contact_id = $this->civicrm->contact->get_for_current_user();
		}

		// --<
		return $contact_id;

	}



	/**
	 * Finds the Contact ID in the data from the mapped Contact ID Field.
	 *
	 * @since 0.5.1
	 *
	 * @param array $contact_data The array of Contact data.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_from_field( $contact_data ) {

		// Init return.
		$contact_id = false;

		// Bail if there's no Contact ID in the incoming data.
		if ( empty( $contact_data['id'] ) || ! is_numeric( $contact_data['id'] ) ) {
			return $contact_id;
		}

		// Use the incoming data.
		$contact_id = (int) $contact_data['id'];

		// --<
		return $contact_id;

	}

	/**
	 * Gets the Contact ID if it exists in the Form Action Query Vars array.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return integer $offset The Relationship offset.
	 */
	public function form_contact_id_get_existing( $form, $current_post_id, $action ) {

		// Init return.
		$contact_id = false;

		// On load, the Form Actions array may already be populated.
		$alias = get_sub_field( $this->field_key . 'custom_alias' );

		// Get the existing Form Actions.
		$form_actions = acfe_form_get_actions();

		// Bail if there aren't any.
		if ( empty( $form_actions ) ) {
			return $contact_id;
		}

		// Bail if there's no entry for this alias.
		if ( ! array_key_exists( $alias, $form_actions ) ) {
			return $contact_id;
		}

		// Grab the entry.
		$form_action = $form_actions[ $alias ];

		// Bail if we can't find the Contact ID.
		if ( empty( $form_action['contact']['id'] ) ) {
			return $contact_id;
		}

		// --<
		return (int) $form_action['contact']['id'];

	}



	/**
	 * Finds the Contact ID when this is a "Related Contact".
	 *
	 * @since 0.5
	 *
	 * @param array $relationship_data The array of Relationship data.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_related( $relationship_data ) {

		// Init return.
		$contact_id = false;

		// Check the "Form Submitter" Contact Field.
		$submitter_contact = get_sub_field( $this->field_key . 'submitting_contact' );

		// Bail if Submitting Contact Action.
		if ( $submitter_contact ) {
			return $contact_id;
		}

		// Bail if no Relationship data.
		if ( empty( $relationship_data ) ) {
			return $contact_id;
		}

		// Let's inspect each of them.
		foreach ( $relationship_data as $field ) {

			// Get the related Contact Action Name.
			$action_name = $field['action_ref'];

			// We need an Action Name.
			if ( empty( $action_name ) ) {
				continue;
			}

			// Get the Contact data for that Action.
			$related_contact = acfe_form_get_action( $action_name, 'contact' );
			if ( empty( $related_contact['id'] ) ) {
				continue;
			}

			// If we have an existing Relationship.
			if ( ! empty( $field['id'] ) ) {

				// Use Contact ID that is NOT the related Contact.
				if ( $related_contact['id'] === $field['contact_id_a'] ) {
					$contact_id = $field['contact_id_b'];
				} else {
					$contact_id = $field['contact_id_a'];
				}

				// Found it, so break.
				break;

			}

		}

		// --<
		return $contact_id;

	}



	/**
	 * Finds the Contact ID given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_data The array of Contact data.
	 * @param array $email_data The array of Email data.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_deduped( $contact_data, $email_data ) {

		// Init return.
		$contact_id = false;

		// Add the Primary Email.
		$primary_email = '';
		foreach ( $email_data as $email_array ) {
			if ( $email_array['is_primary'] ) {
				$contact_data['email'] = $email_array['email'];
				break;
			}
		}

		// Get the chosen Dedupe Rule.
		$dedupe_rule_id = get_sub_field( $this->field_key . 'dedupe_rules' );

		// If a Dedupe Rule is selected, use it.
		if ( ! empty( $dedupe_rule_id ) ) {
			$contact_id = $this->civicrm->contact->get_by_dedupe_rule( $contact_data, $contact_data['contact_type'], $dedupe_rule_id );
		} else {
			// Use the default unsupervised rule.
			// NOTE: We need the Email Address to use the default unsupervised rule.
			$contact_id = $this->civicrm->contact->get_by_dedupe_unsupervised( $contact_data, $contact_data['contact_type'] );
		}

		// --<
		return $contact_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Custom Field data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Init File Fields tracker.
		$file_fields = [];

		// Build data array.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Fresh Fields array.
			$fields = [];

			// Get Group Field.
			$custom_group_field = get_sub_field( $this->field_key . 'custom_group_' . $custom_group['id'] );
			foreach ( $custom_group_field as $field ) {

				// Get mapped Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {

					// Add to mapped Fields array.
					$code = 'custom_' . $custom_field['id'];
					$fields[ $code ] = $custom_group_field[ $this->field_name . 'map_' . $code ];

					// Track any "File" Custom Fields.
					if ( $custom_field['data_type'] === 'File' ) {
						$file_fields[ $code ] = $fields[ $code ];
					}

				}

			}

			// Populate data array with values of mapped Fields.
			$data += acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		}

		// Post-process data for File Fields.
		if ( ! empty( $file_fields ) ) {
			foreach ( $file_fields as $code => $field_ref ) {

				// Get the ACF Field settings.
				$selector = acfe_form_map_field_value_load( $field_ref, $current_post_id, $form );
				$settings = get_field_object( $selector, $current_post_id );

				// Skip if "CiviCRM only" and no File was uploaded.
				if ( empty( $data[ $code ] ) ) {
					if ( ! empty( $settings['civicrm_file_no_wp'] ) ) {
						continue;
					}
				}

				// Flag for possible deletion if no File was uploaded.
				if ( empty( $data[ $code ] ) ) {
					$this->file_fields_empty[ $code ] = [
						'field' => $field_ref,
						'selector' => $selector,
						'settings' => $settings,
					];
				}

				// Get the processed value (the Attachment ID).
				$attachment_id = (int) $data[ $code ];

				// Build an args array.
				$args = [
					'selector' => $selector,
					'post_id' => $current_post_id,
				];

				// Overwrite entry in data array with data for CiviCRM.
				$data[ $code ] = $this->civicrm->attachment->value_get_for_civicrm( $attachment_id, $settings, $args );

				// Maybe delete the WordPress Attachment.
				if ( ! empty( $settings['civicrm_file_no_wp'] ) ) {
					wp_delete_attachment( $attachment_id, true );
				}

			}
		}

		// --<
		return $data;

	}



	/**
	 * Processes Custom Fields once a Contact has been established.
	 *
	 * This is used when a File has been "deleted" and the ACF Field is set not
	 * to delete the WordPress Attachment. In such cases, the ACF "File" Field
	 * may be auto-populated in the Form - so "deleting" it is assumed to mean
	 * that the submitter wishes to delete the WordPress Attachment and the
	 * content of the CiviCRM Custom Field.
	 *
	 * This is only possible because sending an empty value to the API for the
	 * CiviCRM Custom Field will cause the update process to be skipped for
	 * Custom Fields of type "File" - so the previous value will still exist.
	 *
	 * @since 0.5.2
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @param array $contact The array of Contact data.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_post_process( $form, $current_post_id, $action, $contact ) {

		// Bail if we have no post-process array.
		if ( empty( $this->file_fields_empty ) ) {
			return;
		}

		// Bail if we have no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return;
		}

		// Get the array of Custom Field IDs.
		$custom_field_ids = array_keys( $this->file_fields_empty );
		array_walk( $custom_field_ids, function( &$item ) {
			$item = (int) trim( str_replace( 'custom_', '', $item ) );
		} );

		// Get the corresponding values.
		$values = $this->plugin->civicrm->custom_field->values_get_by_contact_id( $contact['id'], $custom_field_ids );
		if ( empty( $values ) ) {
			return;
		}

		// Handle each "deleted" Field.
		foreach ( $values as $custom_field_id => $file_id ) {

			// Sanity check.
			if ( empty( $this->file_fields_empty[ 'custom_' . $custom_field_id ] ) ) {
				continue;
			}

			// Skip if there's no Custom Field value.
			if ( empty( $file_id ) ) {
				continue;
			}

			// Get the data from the post-process array.
			$data = $this->file_fields_empty[ 'custom_' . $custom_field_id ];

			// Build args.
			$args = [
				'entity_id' => $contact['id'],
				'custom_field_id' => $custom_field_id,
			];

			// Hand off to Attachment class.
			$this->civicrm->attachment->fields_clear( (int) $file_id, $data['settings'], $args );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Email data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $email_data The array of Email data.
	 */
	public function form_email_data( $form, $current_post_id, $action ) {

		// Init return.
		$email_data = [];

		// Get the Email Repeater Field.
		$email_repeater = get_sub_field( $this->field_key . 'email_repeater' );

		// Skip it if it's empty.
		if ( empty( $email_repeater ) ) {
			return $email_data;
		}

		// Loop through the Action Fields.
		foreach ( $email_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Always get Location Type.
			$fields['location_type_id'] = $field[ $this->field_name . 'map_email_location_type_id' ];

			// Get mapped Fields.
			foreach ( $this->email_fields as $email_field ) {
				$fields[ $email_field['name'] ] = $field[ $this->field_name . 'map_email_' . $email_field['name'] ];
			}

			// Get Email Conditional.
			$fields['email_conditional'] = $field[ $this->field_name . 'map_email_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Email Conditional Reference.
			$fields['email_conditional_ref'] = $field[ $this->field_name . 'map_email_conditional' ];

			// Add the data.
			$email_data[] = $fields;

		}

		// --<
		return $email_data;

	}



	/**
	 * Saves the CiviCRM Email(s) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $email_data The array of Email data.
	 * @return array|bool $emails The array of Emails, or false on failure.
	 */
	public function form_email_save( $contact, $email_data ) {

		// Init return.
		$emails = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $emails;
		}

		// Bail if there's no Email data.
		if ( empty( $email_data ) ) {
			return $emails;
		}

		// Handle each nested Action in turn.
		foreach ( $email_data as $email ) {

			// Strip out empty Fields.
			$email = $this->form_data_prepare( $email );

			// Only skip if the Email Conditional Reference Field has a value.
			if ( ! empty( $email['email_conditional_ref'] ) ) {
				// And the Email Conditional Field has a value.
				if ( empty( $email['email_conditional'] ) ) {
					continue;
				}
			}

			// TODO: Do we need a "Delete record if Email is empty" option?

			// Skip if there is no Email Address to save.
			if ( empty( $email['email'] ) ) {
				continue;
			}

			// Update the Email.
			$result = $this->civicrm->email->email_record_update( $contact['id'], $email );

			// Skip on failure.
			if ( $result === false ) {
				continue;
			}

			// Get the full Email data.
			$emails[] = $this->civicrm->email->email_get_by_id( $result['id'] );

		}

		// --<
		return $emails;

	}



	/**
	 * Gets the CiviCRM Primary Email from parsed data.
	 *
	 * This is used to find an Email Address to use as the Contact "Display Name"
	 * when no "First Name" & "Last Name" or "Display Name" are provided in the
	 * Form data and a Contact is being created. This ensures that a Contact is
	 * created, mirroring how the CiviCRM UI works.
	 *
	 * @since 0.5.2
	 *
	 * @param array $email_data The array of Email data.
	 * @return string|bool $email The Email Address, or false on failure.
	 */
	public function form_email_primary_get( $email_data ) {

		// Init return.
		$email = false;

		// Bail if there's no Email data.
		if ( empty( $email_data ) ) {
			return $email;
		}

		// Handle each item in turn.
		foreach ( $email_data as $email_item ) {

			// Strip out empty Fields.
			$email_item = $this->form_data_prepare( $email_item );

			// Only skip if the Email Conditional Reference Field has a value.
			if ( ! empty( $email_item['email_conditional_ref'] ) ) {
				// And the Email Conditional Field has a value.
				if ( empty( $email_item['email_conditional'] ) ) {
					continue;
				}
			}

			// Skip if there's no Email.
			if ( empty( $email_item['email'] ) ) {
				continue;
			}

			// If we find the Primary Email, skip the rest.
			if ( ! empty( $email_item['is_primary'] ) ) {
				$email = $email_item['email'];
				break;
			}

			/*
			 * Let's set the return so it is populated with something - in case
			 * there is no Primary Email in the data.
			 */
			$email = $email_item['email'];

		}

		// --<
		return $email;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Relationship data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $relationship_data The array of Relationship data.
	 */
	public function form_relationship_data( $form, $current_post_id, $action ) {

		// Init return.
		$relationship_data = [];

		// Get the Relationship Repeater Field.
		$relationship_repeater = get_sub_field( $this->field_key . 'relationship_repeater' );

		// Skip it if it's empty.
		if ( empty( $relationship_repeater ) ) {
			return $relationship_data;
		}

		// Loop through the Action Fields.
		foreach ( $relationship_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Always get Action Reference.
			$fields['action_ref'] = $field[ $this->field_name . 'relationship_action_ref' ];
			$fields['relationship_type'] = $field[ $this->field_name . 'relationship_type' ];

			// Get mapped Fields.
			foreach ( $this->relationship_fields as $relationship_field ) {
				$fields[ $relationship_field['name'] ] = $field[ $this->field_name . 'map_' . $relationship_field['name'] ];
			}

			// Maybe add Custom Fields.
			$custom_fields = $this->form_relationship_custom_data( $field );
			if ( ! empty( $custom_fields ) ) {
				$fields += $custom_fields;
			}

			// Populate data array with values of mapped Fields.
			$relationship_data[] = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		}

		// Init parsed array.
		$relationship_parsed = [];

		// Let's inspect each of them.
		foreach ( $relationship_data as $field ) {

			// Get the related Contact Action Name.
			$action_name = $field['action_ref'];

			// We need an Action Name.
			if ( empty( $action_name ) ) {
				continue;
			}

			// Get the Contact data for that Action.
			$related_contact = acfe_form_get_action( $action_name, 'contact' );
			if ( empty( $related_contact['id'] ) ) {
				continue;
			}

			// Get Relationship to related Contact.
			$relationship_type = $field['relationship_type'];

			// Get the Relationship Type ID and direction.
			$relationship_array = explode( '_', $relationship_type );
			$type_id = (int) $relationship_array[0];
			$direction = $relationship_array[1];

			// Get the inverse direction.
			$inverse = 'equal';
			if ( $direction === 'ab' ) {
				$inverse = 'ba';
			}
			if ( $direction === 'ba' ) {
				$inverse = 'ab';
			}

			// Get the directional Relationship(s).
			$relationships = $this->civicrm->relationship->get_directional( $related_contact['id'], $type_id, $inverse );

			// If there isn't one, build array to create.
			if ( empty( $relationships ) ) {

				// Set the related Contact ID.
				if ( $inverse === 'ab' ) {
					$field['contact_id_a'] = $related_contact['id'];
				} else {
					$field['contact_id_b'] = $related_contact['id'];
				}

				// Assign extracted Type ID.
				$field['relationship_type_id'] = $type_id;

				// Use incoming data.
				$relationship_parsed[] = $field;

				continue;

			}

			// Let's reset the keys since the array is keyed by Relationship ID.
			$relationships = array_values( $relationships );

			// Get the Relationship offset.
			$offset = $this->form_relationship_offset( $relationships, $type_id, $related_contact['id'], $inverse );

			// If there isn't one, build array to create.
			if ( ! array_key_exists( $offset, $relationships ) ) {

				// Set the related Contact ID.
				if ( $inverse === 'ab' ) {
					$field['contact_id_a'] = $related_contact['id'];
				} else {
					$field['contact_id_b'] = $related_contact['id'];
				}

				// Assign extracted Type ID.
				$field['relationship_type_id'] = $type_id;

				// Use incoming data.
				$relationship_parsed[] = $field;

				continue;

			}

			// Grab the Relationship with the offset.
			$relationship = $relationships[ $offset ];

			// Parse against the incoming data.
			$relationship_update = [];

			// Overwrite when there is an incoming value.
			foreach ( $relationship as $key => $value ) {
				if ( ! empty( $field[ $key ] ) ) {
					$relationship_update[ $key ] = $field[ $key ];
				} else {
					$relationship_update[ $key ] = $value;
				}
			}

			// Add in any entries that don't exist.
			foreach ( $field as $key => $value ) {
				if ( ! array_key_exists( $key, $relationship ) ) {
					$relationship_update[ $key ] = $field[ $key ];
				}
			}

			// Finally, add to parsed array.
			$relationship_parsed[] = $relationship_update;

		}

		// --<
		return $relationship_parsed;

	}



	/**
	 * Builds Relationship Custom Field data array from mapped Fields.
	 *
	 * @since 0.5.1
	 *
	 * @param array $field The currently processed Repeater Field.
	 * @return array $fields The array of Custom Fields data.
	 */
	public function form_relationship_custom_data( $field ) {

		// Init return.
		$fields = [];

		// Build data array.
		foreach ( $this->relationship_custom_fields as $key => $custom_group ) {

			// Get Group Field.
			$custom_group_field = $field[ $this->field_name . 'relationship_custom_group_' . $custom_group['id'] ];
			foreach ( $custom_group_field as $group_field ) {

				// Get mapped Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$code = 'custom_' . $custom_field['id'];
					$fields[ $code ] = $custom_group_field[ $this->field_name . 'map_' . $code ];
				}

			}

		}

		// --<
		return $fields;

	}



	/**
	 * Saves the CiviCRM Relationship(s) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $relationship_data The array of Relationship data.
	 * @return array|bool $relationships The array of Relationships, or false on failure.
	 */
	public function form_relationship_save( $contact, $relationship_data ) {

		// Init return.
		$relationships = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $relationships;
		}

		// Bail if there's no Relationship data.
		if ( empty( $relationship_data ) ) {
			return $relationships;
		}

		// Handle each nested Action in turn.
		foreach ( $relationship_data as $relationship ) {

			// Strip out empty Fields.
			$relationship = $this->form_data_prepare( $relationship );

			// Assign Contact ID when it's a new Relationship.
			if ( empty( $relationship['id'] ) ) {
				if ( ! empty( $relationship['contact_id_b'] ) ) {
					$relationship['contact_id_a'] = $contact['id'];
				} else {
					$relationship['contact_id_b'] = $contact['id'];
				}
			}

			// Update the Relationship.
			$result = $this->civicrm->relationship->relationship_record_update( $relationship );

			// Skip on failure.
			if ( $result === false ) {
				continue;
			}

			// Get the full Relationship data.
			$relationships[] = $this->civicrm->relationship->get_by_id( $result['id'] );

		}

		// --<
		return $relationships;

	}



	/**
	 * Gets the current Relationship offset.
	 *
	 * This method examines previously-parsed Contact Actions to see if there are
	 * any of the same Relationship Type. If it finds any, it returns an offset
	 * that allows discovery of the current Contact from the array of Contacts
	 * that are related to the target Contact.
	 *
	 * @since 0.5
	 *
	 * @param array $relationships The array of Relationships data.
	 * @param integer $type_id The numeric ID of the Relationship Type.
	 * @param integer $related_contact_id The numeric ID of the Related Contact.
	 * @param string $direction The direction of the Relationship.
	 * @return integer $offset The Relationship offset.
	 */
	public function form_relationship_offset( $relationships, $type_id, $related_contact_id, $direction ) {

		// Init as first.
		$offset = 0;

		// Get the existing Form Actions.
		$form_actions = acfe_form_get_actions();

		// Bail if there aren't any.
		if ( empty( $form_actions ) ) {
			return $offset;
		}

		// Look at each to determine the offset.
		foreach ( $form_actions as $key => $form_action ) {

			// Skip the "previous Action of this kind".
			if ( $key == $this->action_name ) {
				continue;
			}

			// Skip Actions that are not Contact Actions.
			if ( empty( $form_action['form_action'] ) || $form_action['form_action'] !== $this->action_name ) {
				continue;
			}

			// Skip Actions that have no Relationships.
			if ( empty( $form_action['relationships'] ) ) {
				continue;
			}

			// See if there are any of the same Type.
			foreach ( $form_action['relationships'] as $relationship ) {

				// Make sure it's an array.
				$relationship = (array) $relationship;

				// Skip any that aren't of the same Relationship Type.
				if ( $relationship['relationship_type_id'] != $type_id ) {
					continue;
				}

				// Skip when neither Contact ID is the related Contact for "equal" Relationships.
				if ( $direction === 'equal' ) {
					if ( $relationship['contact_id_b'] != $related_contact_id ) {
						if ( $relationship['contact_id_a'] != $related_contact_id ) {
							continue;
						}
					}
				}

				// Get the related Contact ID for "directional" Relationships.
				if ( $direction === 'ab' ) {
					$contact_id = $relationship['contact_id_a'];
				} else {
					$contact_id = $relationship['contact_id_b'];
				}

				// Skip those that don't relate to the same Contact.
				if ( $direction !== 'equal' && $contact_id != $related_contact_id ) {
					continue;
				}

				// Increment offset.
				$offset++;

			}

		}

		// --<
		return $offset;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Website data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $website_data The array of Website data.
	 */
	public function form_website_data( $form, $current_post_id, $action ) {

		// Init return.
		$website_data = [];

		// Get the Website Repeater Field.
		$website_repeater = get_sub_field( $this->field_key . 'website_repeater' );

		// Skip it if it's empty.
		if ( empty( $website_repeater ) ) {
			return $website_data;
		}

		// Loop through the Action Fields.
		foreach ( $website_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Always get Website Type.
			$fields['website_type_id'] = $field[ $this->field_name . 'map_website_type_id' ];

			// Get mapped Fields.
			foreach ( $this->website_fields as $website_field ) {
				$fields[ $website_field['name'] ] = $field[ $this->field_name . 'map_website_' . $website_field['name'] ];
			}

			// Get Website Conditional.
			$fields['website_conditional'] = $field[ $this->field_name . 'map_website_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Website Conditional Reference.
			$fields['website_conditional_ref'] = $field[ $this->field_name . 'map_website_conditional' ];

			// Add the data.
			$website_data[] = $fields;

		}

		// --<
		return $website_data;

	}



	/**
	 * Saves the CiviCRM Website(s) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $website_data The array of Website data.
	 * @return array|bool $websites The array of Websites, or false on failure.
	 */
	public function form_website_save( $contact, $website_data ) {

		// Init return.
		$websites = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $websites;
		}

		// Bail if there's no Website data.
		if ( empty( $website_data ) ) {
			return $websites;
		}

		// Handle each nested Action in turn.
		foreach ( $website_data as $website ) {

			// Strip out empty Fields.
			$website = $this->form_data_prepare( $website );

			// Only skip if the Website Conditional Reference Field has a value.
			if ( ! empty( $website['website_conditional_ref'] ) ) {
				// And the Website Conditional Field has a value.
				if ( empty( $website['website_conditional'] ) ) {
					continue;
				}
			}

			// TODO: Do we need a "Delete record if Website is empty" option?

			// Skip if there's no Website URL.
			if ( empty( $website['url'] ) ) {
				continue;
			}

			// Update the Website.
			$result = $this->plugin->civicrm->website->update_for_contact( $website['website_type_id'], $contact['id'], $website['url'] );
			if ( $result === false ) {
				continue;
			}

			// Get the full Website data.
			$websites[] = $this->civicrm->website->website_get_by_id( $result['id'] );

		}

		// --<
		return $websites;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Address data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $address_data The array of Address data.
	 */
	public function form_address_data( $form, $current_post_id, $action ) {

		// Init return.
		$address_data = [];

		// Get the Address Repeater Field.
		$address_repeater = get_sub_field( $this->field_key . 'address_repeater' );

		// Skip it if it's empty.
		if ( empty( $address_repeater ) ) {
			return $address_data;
		}

		// Loop through the Action Fields.
		foreach ( $address_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Always get Location Type.
			$fields['location_type_id'] = $field[ $this->field_name . 'map_address_location_type_id' ];

			// Always get "Include empty Fields".
			$fields['is_override'] = $field[ $this->field_name . 'is_override' ];

			// Get mapped Fields.
			foreach ( $this->address_fields as $address_field ) {
				$fields[ $address_field['name'] ] = $field[ $this->field_name . 'map_address_' . $address_field['name'] ];
			}

			// Maybe add Custom Fields.
			$custom_fields = $this->form_address_custom_data( $field );
			if ( ! empty( $custom_fields ) ) {
				$fields += $custom_fields;
			}

			// Get Address Conditional.
			$fields['address_conditional'] = $field[ $this->field_name . 'map_address_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Address Conditional Reference.
			$fields['address_conditional_ref'] = $field[ $this->field_name . 'map_address_conditional' ];

			// Add the data.
			$address_data[] = $fields;

		}

		// --<
		return $address_data;

	}



	/**
	 * Builds Address Custom Field data array from mapped Fields.
	 *
	 * @since 0.5.1
	 *
	 * @param array $field The currently processed Repeater Field.
	 * @return array $fields The array of Custom Fields data.
	 */
	public function form_address_custom_data( $field ) {

		// Init return.
		$fields = [];

		// Build data array.
		foreach ( $this->address_custom_fields as $key => $custom_group ) {

			// Get mapped Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$fields[ $code ] = $field[ $this->field_name . 'map_' . $code ];
			}

		}

		// --<
		return $fields;

	}



	/**
	 * Saves the CiviCRM Address(es) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $address_data The array of Address data.
	 * @return array|bool $addresses The array of Addresses, or false on failure.
	 */
	public function form_address_save( $contact, $address_data ) {

		// Init return.
		$addresses = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $addresses;
		}

		// Bail if there's no Address data.
		if ( empty( $address_data ) ) {
			return $addresses;
		}

		// Handle each nested Action in turn.
		foreach ( $address_data as $address ) {

			// Strip out empty Fields.
			$address = $this->form_data_prepare( $address );

			// Only skip if the Address Conditional Reference Field has a value.
			if ( ! empty( $address['address_conditional_ref'] ) ) {
				// And the Address Conditional Field has a value.
				if ( empty( $address['address_conditional'] ) ) {
					continue;
				}
			}

			// Add in empty Fields when requested.
			if ( ! empty( $address['is_override'] ) ) {
				foreach ( $this->address_fields as $address_field ) {
					if ( ! array_key_exists( $address_field['name'], $address ) ) {
						$address[ $address_field['name'] ] = '';
					}
				}
			}

			// Update the Address.
			$result = $this->plugin->civicrm->address->address_record_update( $contact['id'], $address );

			// Skip on failure.
			if ( $result === false ) {
				continue;
			}

			// Get the full Address data.
			$addresses[] = $this->plugin->civicrm->address->address_get_by_id( $result['id'] );

		}

		// --<
		return $addresses;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Phone data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $phone_data The array of Phone data.
	 */
	public function form_phone_data( $form, $current_post_id, $action ) {

		// Init return.
		$phone_data = [];

		// Get the Phone Repeater Field.
		$phone_repeater = get_sub_field( $this->field_key . 'phone_repeater' );

		// Skip it if it's empty.
		if ( empty( $phone_repeater ) ) {
			return $phone_data;
		}

		// Loop through the Action Fields.
		foreach ( $phone_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Always get Location Type.
			$fields['location_type_id'] = $field[ $this->field_name . 'map_phone_location_type_id' ];

			// Always get Phone Type.
			$fields['phone_type_id'] = $field[ $this->field_name . 'map_phone_type_id' ];

			// Get mapped Fields.
			foreach ( $this->phone_fields as $phone_field ) {
				$fields[ $phone_field['name'] ] = $field[ $this->field_name . 'map_phone_' . $phone_field['name'] ];
			}

			// Get Phone Conditional.
			$fields['phone_conditional'] = $field[ $this->field_name . 'map_phone_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Phone Conditional Reference.
			$fields['phone_conditional_ref'] = $field[ $this->field_name . 'map_phone_conditional' ];

			// Add the data.
			$phone_data[] = $fields;

		}

		// --<
		return $phone_data;

	}



	/**
	 * Saves the CiviCRM Phone(s) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $phone_data The array of Phone data.
	 * @return array|bool $phones The array of Phones, or false on failure.
	 */
	public function form_phone_save( $contact, $phone_data ) {

		// Init return.
		$phones = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $phones;
		}

		// Bail if there's no Phone data.
		if ( empty( $phone_data ) ) {
			return $phones;
		}

		// Handle each nested Action in turn.
		foreach ( $phone_data as $phone ) {

			// Strip out empty Fields.
			$phone = $this->form_data_prepare( $phone );

			// Skip if there's no Phone Number.
			if ( empty( $phone['phone'] ) ) {
				continue;
			}

			// Only skip if the Phone Conditional Reference Field has a value.
			if ( ! empty( $phone['phone_conditional_ref'] ) ) {
				// And the Phone Conditional Field has a value.
				if ( empty( $phone['phone_conditional'] ) ) {
					continue;
				}
			}

			// Try and get the Phone Record.
			$location_type_id = $phone['location_type_id'];
			$phone_type_id = $phone['phone_type_id'];
			$phone_records = $this->plugin->civicrm->phone->phones_get_by_type( $contact['id'], $location_type_id, $phone_type_id );

			// We cannot handle more than one, though CiviCRM allows many.
			if ( count( $phone_records ) > 1 ) {
				continue;
			}

			// Add ID to update if found.
			if ( ! empty( $phone_records ) ) {
				$phone_record = (array) array_pop( $phone_records );
				$phone['id'] = $phone_record['id'];
			}

			// Create/update the Phone Record.
			$result = $this->plugin->civicrm->phone->update( $contact['id'], $phone );

			// Skip on failure.
			if ( $result === false ) {
				continue;
			}

			// Get the full Phone data.
			$phones[] = $this->plugin->civicrm->phone->phone_get_by_id( $result['id'] );

		}

		// --<
		return $phones;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Instant Messenger data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $im_data The array of Instant Messenger data.
	 */
	public function form_im_data( $form, $current_post_id, $action ) {

		// Init return.
		$im_data = [];

		// Get the Instant Messenger Repeater Field.
		$im_repeater = get_sub_field( $this->field_key . 'im_repeater' );

		// Skip it if it's empty.
		if ( empty( $im_repeater ) ) {
			return $im_data;
		}

		// Loop through the Action Fields.
		foreach ( $im_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Always get Location Type.
			$fields['location_type_id'] = $field[ $this->field_name . 'map_im_location_type_id' ];

			// Always get Instant Messenger Type.
			$fields['provider_id'] = $field[ $this->field_name . 'map_provider_id' ];

			// Get mapped Fields.
			foreach ( $this->im_fields as $im_field ) {
				$fields[ $im_field['name'] ] = $field[ $this->field_name . 'map_im_' . $im_field['name'] ];
			}

			// Get Instant Messenger Conditional.
			$fields['im_conditional'] = $field[ $this->field_name . 'map_im_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Instant Messenger Conditional Reference.
			$fields['im_conditional_ref'] = $field[ $this->field_name . 'map_im_conditional' ];

			// Add the data.
			$im_data[] = $fields;

		}

		// --<
		return $im_data;

	}



	/**
	 * Saves the CiviCRM Instant Messenger(s) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $im_data The array of Instant Messenger data.
	 * @return array|bool $ims The array of Instant Messengers, or false on failure.
	 */
	public function form_im_save( $contact, $im_data ) {

		// Init return.
		$ims = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $ims;
		}

		// Bail if there's no Instant Messenger data.
		if ( empty( $im_data ) ) {
			return $ims;
		}

		// Handle each nested Action in turn.
		foreach ( $im_data as $im ) {

			// Strip out empty Fields.
			$im = $this->form_data_prepare( $im );

			// Only skip if the Instant Messenger Conditional Reference Field has a value.
			if ( ! empty( $im['im_conditional_ref'] ) ) {
				// And the Instant Messenger Conditional Field has a value.
				if ( empty( $im['im_conditional'] ) ) {
					continue;
				}
			}

			// TODO: Do we need a "Delete record if Instant Messenger is empty" option?

			// Skip if there's no Instant Messenger.
			if ( empty( $im['name'] ) ) {
				continue;
			}

			// Try and get the Phone Record.
			$location_type_id = $im['location_type_id'];
			$provider_id = $im['provider_id'];
			$im_records = $this->civicrm->im->ims_get_by_type( $contact['id'], $location_type_id, $provider_id );

			// We cannot handle more than one, though CiviCRM allows many.
			if ( count( $im_records ) > 1 ) {
				continue;
			}

			// Add ID to update if found.
			if ( ! empty( $im_records ) ) {
				$im_record = (array) array_pop( $im_records );
				$im['id'] = $im_record['id'];
			}

			// Update the Instant Messenger.
			$result = $this->civicrm->im->update( $contact['id'], $im );

			// Skip on failure.
			if ( $result === false ) {
				continue;
			}

			// Get the full Instant Messenger data.
			$ims[] = $this->civicrm->im->im_get_by_id( $result['id'] );

		}

		// --<
		return $ims;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Group data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $group_data The array of Group data.
	 */
	public function form_group_data( $form, $current_post_id, $action ) {

		// Init return.
		$group_data = [];

		// Get the Group Repeater Field.
		$group_repeater = get_sub_field( $this->field_key . 'group_repeater' );

		// Skip it if it's empty.
		if ( empty( $group_repeater ) ) {
			return $group_data;
		}

		// Loop through the Action Fields.
		foreach ( $group_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get Group ID.
			$fields['group_id'] = $field[ $this->field_name . 'map_group_id' ];

			// Get Group Conditional.
			$fields['group_conditional'] = $field[ $this->field_name . 'map_group_conditional' ];

			// Get "Enable double opt-in".
			$fields['double_optin'] = $field[ $this->field_name . 'double_optin' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Group Conditional Reference.
			$fields['group_conditional_ref'] = $field[ $this->field_name . 'map_group_conditional' ];

			// Add the data.
			$group_data[] = $fields;

		}

		// --<
		return $group_data;

	}



	/**
	 * Adds the Contact to the CiviCRM Group(s) given data from Group Actions.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $group_data The array of Group data.
	 * @return array|bool $groups The array of Groups, or false on failure.
	 */
	public function form_group_save( $contact, $group_data ) {

		// Init return.
		$groups = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $groups;
		}

		// Bail if there's no Group data.
		if ( empty( $group_data ) ) {
			return $groups;
		}

		// Handle each nested Action in turn.
		foreach ( $group_data as $group ) {

			// Only skip if the Group Conditional Reference Field has a value.
			if ( ! empty( $group['group_conditional_ref'] ) ) {
				// And the Group Conditional Field has a value.
				if ( empty( $group['group_conditional'] ) ) {
					continue;
				}
			}

			// TODO: Do we need a "Remove from Group if Group is empty" option?

			// Skip if there's no Group ID.
			if ( empty( $group['group_id'] ) ) {
				continue;
			}

			// Skip if already a Group Member.
			$is_member = $this->civicrm->group->group_contact_exists( $group['group_id'], $contact['id'] );
			if ( $is_member === true ) {
				continue;
			}

			// Add with or without Opt In.
			if ( empty( $group['double_optin'] ) ) {
				$result = $this->civicrm->group->group_contact_create( $group['group_id'], $contact['id'] );
			} else {
				$result = $this->civicrm->group->group_contact_create_via_opt_in( $group['group_id'], $contact['id'] );
			}

			// Skip adding Group ID on failure.
			if ( $result === false ) {
				continue;
			}

			// Add Group ID to return.
			$groups[] = $group['group_id'];

		}

		// --<
		return $groups;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Membership data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $group_data The array of Membership data.
	 */
	public function form_membership_data( $form, $current_post_id, $action ) {

		// Init return.
		$membership_data = [];

		// Get the Group Repeater Field.
		$membership_repeater = get_sub_field( $this->field_key . 'membership_repeater' );

		// Skip it if it's empty.
		if ( empty( $membership_repeater ) ) {
			return $membership_data;
		}

		// Loop through the Action Fields.
		foreach ( $membership_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get Membership Type ID.
			$fields['membership_type_id'] = $field[ $this->field_name . 'map_membership_type_id' ];

			// Get Membership Conditional.
			$fields['membership_conditional'] = $field[ $this->field_name . 'map_membership_conditional' ];

			/*
			// Get "Enable double opt-in".
			$fields['double_optin'] = $field[ $this->field_name . 'double_optin' ];
			*/

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Membership Conditional Reference.
			$fields['membership_conditional_ref'] = $field[ $this->field_name . 'map_membership_conditional' ];

			// Add the data.
			$membership_data[] = $fields;

		}

		// --<
		return $membership_data;

	}



	/**
	 * Adds the CiviCRM Membership(s) to the Contact given data from Membership Actions.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $membership_data The array of Membership data.
	 * @return array|bool $memberships The array of Memberships, or false on failure.
	 */
	public function form_membership_save( $contact, $membership_data ) {

		// Init return.
		$memberships = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $memberships;
		}

		// Bail if there's no Membership data.
		if ( empty( $membership_data ) ) {
			return $memberships;
		}

		// Handle each nested Action in turn.
		foreach ( $membership_data as $membership ) {

			// Strip out empty Fields.
			$membership = $this->form_data_prepare( $membership );

			// Only skip if the Membership Conditional Reference Field has a value.
			if ( ! empty( $membership['membership_conditional_ref'] ) ) {
				// And the Membership Conditional Field has a value.
				if ( empty( $membership['membership_conditional'] ) ) {
					continue;
				}
			}

			// Skip if there's no Membership Type ID.
			if ( empty( $membership['membership_type_id'] ) ) {
				continue;
			}

			// Skip if Contact already has a current Membership.
			$is_member = $this->civicrm->membership->has_current( $contact['id'], $membership['membership_type_id'] );
			if ( $is_member === true ) {
				continue;
			}

			// Add Contact to Membership data.
			$membership['contact_id'] = $contact['id'];

			// Create the Membership.
			$result = $this->civicrm->membership->create( $membership );

			// Skip adding Membership data on failure.
			if ( $result === false ) {
				continue;
			}

			// Add the full Membership data.
			$memberships[] = $this->civicrm->membership->get_by_id( $result['id'] );

		}

		// --<
		return $memberships;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Note data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $note_data The array of Note data.
	 */
	public function form_note_data( $form, $current_post_id, $action ) {

		// Init return.
		$note_data = [];

		// Get the Note Repeater Field.
		$note_repeater = get_sub_field( $this->field_key . 'note_repeater' );

		// Skip it if it's empty.
		if ( empty( $note_repeater ) ) {
			return $note_data;
		}

		// Loop through the Action Fields.
		foreach ( $note_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get mapped Fields.
			foreach ( $this->note_fields as $note_field ) {
				$fields[ $note_field['name'] ] = $field[ $this->field_name . 'map_note_' . $note_field['name'] ];
			}

			// Get Note Conditional.
			$fields['note_conditional'] = $field[ $this->field_name . 'map_note_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Note Conditional Reference.
			$fields['note_conditional_ref'] = $field[ $this->field_name . 'map_note_conditional' ];

			// Get the data for the Attachment Sub-actions.
			$attachments = $this->form_note_attachments_data( $form, $current_post_id, $action, $field );
			if ( ! empty( $attachments ) ) {
				$fields['attachments'] = $attachments;
			}

			// Add the data.
			$note_data[] = $fields;

		}

		// --<
		return $note_data;

	}



	/**
	 * Saves the CiviCRM Note(s) given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $note_data The array of Note data.
	 * @return array|bool $notes The array of Notes, or false on failure.
	 */
	public function form_note_save( $contact, $note_data ) {

		// Init return.
		$notes = false;

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $notes;
		}

		// Bail if there's no Note data.
		if ( empty( $note_data ) ) {
			return $notes;
		}

		// Handle each nested Action in turn.
		foreach ( $note_data as $note ) {

			// Strip out empty Fields.
			$note = $this->form_data_prepare( $note );

			// Only skip if the Note Conditional Reference Field has a value.
			if ( ! empty( $note['note_conditional_ref'] ) ) {
				// And the Note Conditional Field has a value.
				if ( empty( $note['note_conditional'] ) ) {
					continue;
				}
			}

			// Skip if there's no Note.
			if ( empty( $note['note'] ) ) {
				continue;
			}

			// Add necessary params.
			$note['entity_table'] = 'civicrm_contact';
			$note['entity_id'] = $contact['id'];
			$note['modified_date'] = date( 'YmdHis', strtotime( 'now' ) );

			// Create the Note.
			$result = $this->civicrm->note->create( $note );

			// Skip on failure.
			if ( $result === false ) {
				continue;
			}

			// Get the full Note data.
			$note_full = $this->civicrm->note->get_by_id( $result['id'] );

			// Add Note "Attachment(s)".
			$note_full->attachments = $this->form_note_attachments_save( $note_full, $note['attachments'] );

			// Add the full Note data.
			$notes[] = $note_full;

		}

		// --<
		return $notes;

	}



	/**
	 * Builds Attachment data array from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @param array $note_field The array of Note Field data.
	 * @return array $attachment_data The array of Attachment data.
	 */
	public function form_note_attachments_data( $form, $current_post_id, $action, $note_field ) {

		// Init return.
		$attachment_data = [];

		// Get the Attachment Repeater Field.
		$attachment_repeater = $note_field[ $this->field_name . 'attachment_repeater' ];

		// Skip it if it's empty.
		if ( empty( $attachment_repeater ) ) {
			return $attachment_data;
		}

		// Loop through the Action Fields.
		foreach ( $attachment_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get File Field.
			$fields['file'] = $field[ $this->field_name . 'map_attachment_file' ];

			// Get mapped Fields.
			foreach ( $this->attachment_fields as $attachment_field ) {
				$fields[ $attachment_field['name'] ] = $field[ $this->field_name . 'map_attachment_' . $attachment_field['name'] ];
			}

			// Get Attachment Conditional.
			$fields['attachment_conditional'] = $field[ $this->field_name . 'map_attachment_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Attachment Conditional Reference.
			$fields['attachment_conditional_ref'] = $field[ $this->field_name . 'map_attachment_conditional' ];

			// Add the data.
			$attachment_data[] = $fields;

		}

		// --<
		return $attachment_data;

	}



	/**
	 * Saves the CiviCRM Attachment(s) given data from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array $note The array of Note data.
	 * @param array $attachment_data The array of Attachment data.
	 * @return array|bool $attachments The array of Attachments, or false on failure.
	 */
	public function form_note_attachments_save( $note, $attachment_data ) {

		// Init return.
		$attachments = false;

		// Bail if there's no Note ID.
		if ( empty( $note->id ) ) {
			return $attachments;
		}

		// Bail if there's no Attachment data.
		if ( empty( $attachment_data ) ) {
			return $attachments;
		}

		// Handle each nested Action in turn.
		foreach ( $attachment_data as $attachment ) {

			// Strip out empty Fields.
			$attachment = $this->form_data_prepare( $attachment );

			// Skip if there's no WordPress Attachment ID.
			if ( empty( $attachment['file'] ) ) {
				continue;
			}

			// Only skip if the Attachment Conditional Reference Field has a value.
			if ( ! empty( $attachment['attachment_conditional_ref'] ) ) {
				// And the Attachment Conditional Field has a value.
				if ( empty( $attachment['attachment_conditional'] ) ) {
					continue;
				}
			}

			// Cast Attachment ID as integer.
			$attachment_id = (int) $attachment['file'];

			// Get the WordPress File, Filename and Mime Type.
			$file = get_attached_file( $attachment_id, true );
			$filename = pathinfo( $file, PATHINFO_BASENAME );
			$mime_type = get_post_mime_type( $attachment_id );

			// Build the API params.
			$params = [
				'entity_id' => $note->id,
				'entity_table' => 'civicrm_note',
				'name' => $filename,
				'description' => $attachment['description'],
				'mime_type' => $mime_type,
				'options' => [
					'move-file' => $file,
				],
			];

			// Create the Attachment.
			$result = $this->civicrm->attachment->create( $params );
			if ( $result === false ) {
				continue;
			}

			// Always delete the WordPress Attachment.
			wp_delete_attachment( $attachment_id, true );

			// Get the full Attachment data.
			$attachments[] = $this->civicrm->attachment->get_by_id( $result['id'] );

		}

		// --<
		return $attachments;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Tag data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $tag_data The array of Tag data.
	 */
	public function form_tag_data( $form, $current_post_id, $action ) {

		// Init return.
		$tag_data = [];

		// Get the Tag Repeater Field.
		$tag_repeater = get_sub_field( $this->field_key . 'tag_repeater' );

		// Skip it if it's empty.
		if ( empty( $tag_repeater ) ) {
			return $tag_data;
		}

		// Loop through the Action Fields.
		foreach ( $tag_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get Tag IDs.
			$fields['tag_ids'] = $field[ $this->field_name . 'contact_tags' ];

			// Get Tag Conditional.
			$fields['tag_conditional'] = $field[ $this->field_name . 'map_tag_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Tag Conditional Reference.
			$fields['tag_conditional_ref'] = $field[ $this->field_name . 'map_tag_conditional' ];

			// Add the data.
			$tag_data[] = $fields;

		}

		// --<
		return $tag_data;

	}



	/**
	 * Adds the Contact to the CiviCRM Tag(s) given data from Tag Actions.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param array $tag_data The array of Tag data.
	 * @return array $tags The array of Tags, or empty on failure.
	 */
	public function form_tag_save( $contact, $tag_data ) {

		// Init return.
		$tags = [];

		// Bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			return $tags;
		}

		// Bail if there's no Tag data.
		if ( empty( $tag_data ) ) {
			return $tags;
		}

		// Handle each nested Action in turn.
		foreach ( $tag_data as $tag ) {

			// Only skip if the Tag Conditional Reference Field has a value.
			if ( ! empty( $tag['tag_conditional_ref'] ) ) {
				// And the Tag Conditional Field has a value.
				if ( empty( $tag['tag_conditional'] ) ) {
					continue;
				}
			}

			// TODO: Do we need a "Delete Tag if Tag is empty" option?

			// Skip if there's no Tag ID.
			if ( empty( $tag['tag_ids'] ) ) {
				continue;
			}

			// Handle each Tag in turn.
			foreach ( $tag['tag_ids'] as $tag_id ) {

				// Skip if Contact already has the Tag.
				$has_tag = $this->civicrm->tag->contact_has_tag( $contact['id'], $tag_id );
				if ( $has_tag === true ) {
					$tags[] = $tag_id;
					continue;
				}

				// Add the Tag.
				$result = $this->civicrm->tag->contact_tag_add( $contact['id'], $tag_id );

				// Skip adding Tag ID on failure.
				if ( $result === false ) {
					continue;
				}

				// Add Tag ID to return.
				$tags[] = $tag_id;

			}

		}

		// --<
		return $tags;

	}



} // Class ends.



