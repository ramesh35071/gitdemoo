<?php

/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class UniteCreatorForm{

	const LOGS_OPTIONS_KEY = "unlimited_elements_form_logs";
	const LOGS_MAX_COUNT = 10;

	const PLACEHOLDER_ADMIN_EMAIL = "admin_email";
	const PLACEHOLDER_EMAIL_FIELD = "email_field";
	const PLACEHOLDER_FORM_FIELDS = "form_fields";
	const PLACEHOLDER_SITE_NAME = "site_name";

	private static $isFormIncluded = false;    //indicator that the form included once

	private $formSettings;
	private $formFields;

	/**
	 * add conditions elementor control
	 */
	public static function getConditionsRepeaterSettings(){

		$settings = new UniteCreatorSettings();

		//--- operator

		$params = array();
		$params["origtype"] = UniteCreatorDialogParam::PARAM_DROPDOWN;

		$arrOptions = array("And" => "and", "Or" => "or");

		$settings->addSelect("operator", $arrOptions, __("Operator", "unlimited-elements-for-elementor"), "and", $params);

		//--- field name

		$params = array();
		$params["origtype"] = UniteCreatorDialogParam::PARAM_TEXTFIELD;

		$settings->addTextBox("field_name", "", __("Field Name", "unlimited-elements-for-elementor"), $params);

		//--- condition

		$params = array();
		$params["origtype"] = UniteCreatorDialogParam::PARAM_DROPDOWN;

		$arrOptions = array(
			"=" => "= (equal)",
			">" => "> (more)",
			">=" => ">= (more or equal)",
			"<" => "< (less)",
			"<=" => "<= (less or equal)",
			"!=" => "!= (not equal)");

		$arrOptions = array_flip($arrOptions);

		$settings->addSelect("condition", $arrOptions, __("Condition", "unlimited-elements-for-elementor"), "=", $params);

		//--- value

		$params = array();
		$params["origtype"] = UniteCreatorDialogParam::PARAM_TEXTFIELD;
		$params["label_block"] = true;

		$settings->addTextBox("field_value", "", __("Field Value", "unlimited-elements-for-elementor"), $params);

		return ($settings);
	}

	/**
	 * add form includes
	 */
	public function addFormIncludes(){

		//don't include inside editor

		if(self::$isFormIncluded == true)
			return;

		//include common scripts only once
		if(self::$isFormIncluded == false){
			$urlFormJS = GlobalsUC::$url_assets_libraries . "form/uc_form.js";

			UniteProviderFunctionsUC::addAdminJQueryInclude();
			HelperUC::addScriptAbsoluteUrl_widget($urlFormJS, "uc_form");
		}

		self::$isFormIncluded = true;
	}

	/**
	 * get conditions data
	 * modify the data, add class and attributes
	 */
	public function getVisibilityConditionsParamsData($data, $visibilityParam){

		$name = UniteFunctionsUC::getVal($visibilityParam, "name");

		$arrValue = UniteFunctionsUC::getVal($visibilityParam, "value");

		if(empty($arrValue))
			return ($data);

		$arrValue = UniteFunctionsUC::getVal($arrValue, "{$name}_conditions");

		if(empty($arrValue))
			return ($data);

		$data["ucform_class"] = " ucform-has-conditions";

		return ($data);
	}

	/**
	 * get the list of form logs
	 */
	public static function getFormLogs(){

		$logs = get_option(self::LOGS_OPTIONS_KEY, array());

		return $logs;
	}

	/**
	 * get the form values
	 */
	private function getFieldsData($arrContent, $arrFields){

		$arrOutput = array();

		foreach($arrFields as $arrField){
			// get field input
			$fieldID = UniteFunctionsUC::getVal($arrField, "id");
			$fieldValue = UniteFunctionsUC::getVal($arrField, "value");

			// get saved settings from layout
			$arrFieldSettings = HelperProviderCoreUC_EL::getAddonValuesWithDataFromContent($arrContent, $fieldID);

			// get values that we'll use in the form
			// note - not all the fields will have a name/title
			$name = UniteFunctionsUC::getVal($arrFieldSettings, "field_name");
			$title = UniteFunctionsUC::getVal($arrFieldSettings, "label");
			$required = UniteFunctionsUC::getVal($arrFieldSettings, "required");
			$required = UniteFunctionsUC::strToBool($required);

			$arrFieldOutput = array();
			$arrFieldOutput["title"] = $title;
			$arrFieldOutput["name"] = $name;
			$arrFieldOutput["value"] = $fieldValue;
			$arrFieldOutput["required"] = $required;

			$arrOutput[] = $arrFieldOutput;
		}

		return ($arrOutput);
	}

	/**
	 * submit form
	 */
	public function submitFormFront(){

		$formData = UniteFunctionsUC::getPostGetVariable("formdata", null, UniteFunctionsUC::SANITIZE_NOTHING);
		$formID = UniteFunctionsUC::getPostGetVariable("formId", null, UniteFunctionsUC::SANITIZE_KEY);
		$layoutID = UniteFunctionsUC::getPostGetVariable("postId", null, UniteFunctionsUC::SANITIZE_ID);

		UniteFunctionsUC::validateNotEmpty($formID, "form id");
		UniteFunctionsUC::validateNumeric($layoutID, "post id");

		if(empty($formData))
			UniteFunctionsUC::throwError("No form data found.");

		$arrContent = HelperProviderCoreUC_EL::getElementorContentByPostID($layoutID);

		if(empty($arrContent))
			UniteFunctionsUC::throwError("Elementor content not found.");

		$addonForm = HelperProviderCoreUC_EL::getAddonWithDataFromContent($arrContent, $formID);

		// here can add some validation next...

		$arrFormSettings = $addonForm->getProcessedMainParamsValues();
		$arrFieldsData = $this->getFieldsData($arrContent, $formData);

		$this->doSubmitActions($arrFormSettings, $arrFieldsData);
	}

	/**
	 * submit the form
	 */
	private function doSubmitActions($formSettings, $formFields){

		$this->formSettings = $formSettings;
		$this->formFields = $formFields;

		$data = array();
		$debugData = array("settings" => $formSettings, "fields" => $formFields);
		$debugMessages = array();

		try{
			$debugMessages[] = "Form has been received.";

			$formErrors = $this->validateFields();

			if(empty($formErrors) === false){
				$debugData["errors"] = $formErrors;

				$formErrors = implode(" ", $formErrors);

				UniteFunctionsUC::throwError("Form validation failed ($formErrors).");
			}

			$formActions = UniteFunctionsUC::getVal($formSettings, "form_actions");

			foreach($formActions as $action){
				switch($action){
					case "save":
						$this->createFormEntry();

						$debugMessages[] = "Form entry has been successfully created.";
					break;

					case "email":
					case "email2":
						
						$emailFields = $this->getEmailFields($action);

						$debugData[$action] = $emailFields;
						
						$this->sendEmail($emailFields);
						
						$debugMessages[] = "Email has been successfully sent to {$emailFields["to"]}.";
					break;

					case "webhook":
					case "webhook2":
						$webhookFields = $this->getWebhookFields($action);

						$debugData[$action] = $webhookFields;

						$this->sendWebhook($webhookFields);

						$debugMessages[] = "Webhook has been successfully sent to {$webhookFields["url"]}.";
					break;

					case "redirect":
						$redirectUrl = UniteFunctionsUC::getVal($formSettings, "redirect_url");
						$redirectUrl = esc_url_raw($redirectUrl);

						if(filter_var($redirectUrl, FILTER_VALIDATE_URL) !== false){
							$data["redirect"] = $redirectUrl;

							$debugMessages[] = "Redirecting to $redirectUrl...";
						}
					break;

					default:
						UniteFunctionsUC::throwError("Form action \"$action\" is not implemented.");
				}
			}

			$success = true;
			$message = esc_html__("Form has been successfully submitted.", "unlimited-elements-for-elementor");
		}catch(Exception $e){
			$success = false;
			$message = esc_html__("Unable to submit form.", "unlimited-elements-for-elementor");

			$debugMessages[] = $e->getMessage();
		}

		$this->createFormLog($debugMessages);

		$isDebug = UniteFunctionsUC::getVal($formSettings, "debug_mode");
		$isDebug = UniteFunctionsUC::strToBool($isDebug);

		if($isDebug === true){
			$debugMessage = implode(" ", $debugMessages);
			$debugType = UniteFunctionsUC::getVal($formSettings, "debug_type");

			$data["debug"] = "<p><b>DEBUG:</b> $debugMessage</p>";

			if($debugType === "full"){
				$debugData = json_encode($debugData, JSON_PRETTY_PRINT);
				$debugData = esc_html($debugData);

				$data["debug"] .= "<pre>$debugData</pre>";
			}
		}

		HelperUC::ajaxResponse($success, $message, $data);
	}

	/**
	 * validate fields
	 */
	private function validateFields(){

		$errors = array();

		foreach($this->formFields as $field){
			if($field["required"] === true && $field["value"] === "")
				$errors[] = sprintf(esc_html__("%s field is empty.", "unlimited-elements-for-elementor"), $this->getFieldTitle($field));
		}

		return $errors;
	}

	/**
	 * create form entry
	 */
	private function createFormEntry(){

		$isFormEntriesEnabled = HelperProviderUC::isFormEntriesEnabled();

		if($isFormEntriesEnabled === false)
			return;

		try{
			UniteFunctionsWPUC::processDBTransaction(function(){

				global $wpdb;

				$entriesTable = UniteFunctionsWPUC::prefixDBTable(GlobalsUC::TABLE_FORM_ENTRIES_NAME);

				$entriesData = array(
					"form_name" => $this->getFormName(),
					"post_id" => get_the_ID(),
					"post_title" => get_the_title(),
					"post_url" => get_permalink(),
					"user_id" => get_current_user_id(),
					"user_ip" => UniteFunctionsUC::getUserIp(),
					"user_agent" => UniteFunctionsUC::getUserAgent(),
					"created_at" => current_time("mysql"),
				);

				$isEntryCreated = $wpdb->insert($entriesTable, $entriesData);

				if($isEntryCreated === false){
					throw new Exception($wpdb->last_error);
				}

				$entryId = $wpdb->insert_id;

				$entryFieldsTable = UniteFunctionsWPUC::prefixDBTable(GlobalsUC::TABLE_FORM_ENTRY_FIELDS_NAME);

				foreach($this->formFields as $field){
					$entryFieldsData = array(
						"entry_id" => $entryId,
						"title" => $this->getFieldTitle($field),
						"name" => $field["name"],
						"value" => $field["value"],
					);

					$isFieldCreated = $wpdb->insert($entryFieldsTable, $entryFieldsData);

					if($isFieldCreated === false){
						throw new Exception($wpdb->last_error);
					}
				}
			});
		}catch(Exception $e){
			UniteFunctionsUC::throwError("Unable to create form entry: {$e->getMessage()}");
		}
	}

	/**
	 * create form log
	 */
	private function createFormLog($messages){

		$isFormLogsSavingEnabled = HelperProviderUC::isFormLogsSavingEnabled();

		if($isFormLogsSavingEnabled === false)
			return;

		$logs = self::getFormLogs();

		$logs[] = array(
			"form" => $this->getFormName(),
			"message" => implode(" ", $messages),
			"date" => current_time("mysql"),
		);

		$logs = array_slice($logs, -self::LOGS_MAX_COUNT);

		update_option(self::LOGS_OPTIONS_KEY, $logs);
	}

	/**
	 * send email
	 */
	private function sendEmail($emailFields){
		
		try{
			$validEmail = UniteFunctionsUC::isEmailValid($emailFields["to"]);
			
			if($validEmail === false)
				UniteFunctionsUC::throwError("Invalid \"to\" email address.");
			
			$isSent = wp_mail(
				$emailFields["to"],
				$emailFields["subject"],
				$emailFields["message"],
				$emailFields["headers"]
			);

			if($isSent === false)
				UniteFunctionsUC::throwError("Sending failed.");
		
		}catch(Exception $e){
			UniteFunctionsUC::throwError("Unable to send email to {$emailFields["to"]}: {$e->getMessage()}");
		}
	}

	/**
	 * get email fields
	 */
	private function getEmailFields($fieldPrefix){

		$from = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_from");
		$from = $this->replacePlaceholders($from, array(self::PLACEHOLDER_ADMIN_EMAIL));

		$fromName = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_from_name");
		$fromName = $this->replacePlaceholders($fromName, array(self::PLACEHOLDER_SITE_NAME));

		$replyTo = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_reply_to");
		$replyTo = $this->replacePlaceholders($replyTo, array(self::PLACEHOLDER_ADMIN_EMAIL));

		$to = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_to");
		$to = $this->replacePlaceholders($to, array(self::PLACEHOLDER_ADMIN_EMAIL, self::PLACEHOLDER_EMAIL_FIELD));

		$subject = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_subject");
		$subject = $this->replacePlaceholders($subject, array(self::PLACEHOLDER_SITE_NAME));

		$message = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_message");
		$message = $this->prepareEmailMessageField($message);

		$emailFields = array(
			"from" => $from,
			"from_name" => $fromName,
			"reply_to" => $replyTo,
			"to" => $to,
			"subject" => $subject,
			"message" => $message,
		);

		$emailFields["headers"] = $this->prepareEmailHeaders($emailFields);

		return $emailFields;
	}

	/**
	 * prepare email message field
	 */
	private function prepareEmailMessageField($emailMessage){

		$formFieldsReplace = array();

		foreach($this->formFields as $field){
			$formFieldsReplace[] = "{$this->getFieldTitle($field)}: {$field["value"]}";
		}

		$formFieldsReplace = implode("<br />", $formFieldsReplace);

		$emailMessage = preg_replace("/(\r\n|\r|\n)/", "<br />", $emailMessage); // nl2br

		$emailMessage = $this->replacePlaceholders($emailMessage, array(
			self::PLACEHOLDER_ADMIN_EMAIL,
			self::PLACEHOLDER_SITE_NAME,
			self::PLACEHOLDER_FORM_FIELDS,
		), array(
			self::PLACEHOLDER_FORM_FIELDS => $formFieldsReplace,
		));

		return $emailMessage;
	}

	/**
	 * prepare email headers
	 */
	private function prepareEmailHeaders($emailFields){

		$headers = array();

		if($emailFields["from"]){
			$from = $emailFields["from"];

			if($emailFields["from_name"]){
				$from = "{$emailFields["from_name"]} <{$emailFields["from"]}>";
			}

			$headers[] = "From: $from";
		}

		if($emailFields["reply_to"]){
			$headers[] = "Reply-To: {$emailFields["reply_to"]}";
		}

		return $headers;
	}

	/**
	 * get form name
	 */
	private function getFormName(){

		return $this->formSettings["form_name"] ?: __("Unnamed", "unlimited-elements-for-elementor");
	}

	/**
	 * get field title
	 */
	private function getFieldTitle($field){

		return $field["title"] ?: __("Untitled", "unlimited-elements-for-elementor");
	}

	/**
	 * get placeholder replacement
	 */
	private function getPlaceholderReplace($placeholder){

		switch($placeholder){
			case self::PLACEHOLDER_ADMIN_EMAIL:
				return get_bloginfo("admin_email");

			case self::PLACEHOLDER_EMAIL_FIELD:
				foreach($this->formFields as $field){
					$validEmail = UniteFunctionsUC::isEmailValid($field["value"]);

					if($validEmail === true)
						return $field["value"];
				}

				return "";

			case self::PLACEHOLDER_SITE_NAME:
				return get_bloginfo("name");

			default:
				return "";
		}
	}

	/**
	 * replace placeholders
	 */
	private function replacePlaceholders($value, $placeholders, $additionalReplaces = array()){

		foreach($placeholders as $placeholder){
			if(isset($additionalReplaces[$placeholder]) === true)
				$replace = $additionalReplaces[$placeholder];
			else
				$replace = $this->getPlaceholderReplace($placeholder);

			$value = $this->replacePlaceholder($value, $placeholder, $replace);
		}

		return $value;
	}

	/**
	 * replace placeholder
	 */
	private function replacePlaceholder($value, $placeholder, $replace){

		$value = str_replace("{{$placeholder}}", $replace, $value);

		return $value;
	}

	/**
	 * send webhook
	 */
	private function sendWebhook($webhookFields){

		$args = array(
			"body" => $webhookFields["data"],
		);

		$response = wp_remote_request($webhookFields["url"], $args);
		$status = wp_remote_retrieve_response_code($response);

		if($status !== 200)
			UniteFunctionsUC::throwError("Unable to send webhook to {$webhookFields["url"]}.");
	}

	/**
	 * get webhook fields
	 */
	private function getWebhookFields($fieldPrefix){

		$url = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_url");
		$mode = UniteFunctionsUC::getVal($this->formSettings, $fieldPrefix . "_mode");

		$data = array();

		if($mode === "advanced"){
			$data["form"] = array(
				"name" => $this->getFormName(),
			);

			$data["fields"] = $this->formFields;
		}else{
			foreach($this->formFields as $key => $field){
				$title = $this->getFieldTitle($field);

				if(empty($field["title"]) === true)
					$title .= " " . $key;

				$data[$title] = $field["value"];
			}

			$data["form_name"] = $this->getFormName();
		}

		$webhookFields = array(
			"url" => $url,
			"mode" => $mode,
			"data" => $data,
		);

		return $webhookFields;
	}

}
