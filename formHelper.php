<?php

require_once 'functions.php';

/**
 * A helper class that is used to generate form HTML,
 * so forms can be build much easier in just PHP,
 * without having to manually build out HTML.
 */
class formHelper {

	/**
	 * int - the version number of bootstap being used to render the correct classes.
	 */
	private $bootstrap;

	public function __construct($bootstrap = FALSE) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 *	Render a field label.
	 *	@param $content	string	The label text or HTML contents.
	 *	@param $id		string	The id of the field this is for.
	 */
	function label($content, $id = NULL, $class = NULL) {
		return '<label' . (!empty($id) ? ' for="' . $id . '"' : '') . ((!empty($class)) ? ' class="' . $class . '"' : '') .'>' . $content . '</label>';
	}

	/**
	 *	Render a field label.
	 *	@param $fieldName	string	The name of the field.
	 *	@param $attributes	array	All other options, most of which will appear in on the input tag.
	 */
	function input($fieldName, $attributes = array()) {
		ob_start();

		# Set defaults.
		$attributes += [
			'id' => NULL,
			'label' => FALSE,
			'type' => 'text',
			'value' => NULL,
			'checked' => FALSE,
			'required' => FALSE,
			'class' => '',
			'disabled' => FALSE,
		];

		# These attributes should not appear in the <input> tag.
		$attributesBlackList = [
			'name',
			'label',
			'checked',
			'required',
			'disabled',
		];

		if ($attributes['required']) {
			$attributes['class'] .= ' required';
		}

		if (!empty($attributes['label'])) {
			echo $this->label($attributes['label'], $attributes['id']);
		}

		echo '<input name="' . h($fieldName) . '"';
		foreach ($attributes as $attr_key => $attr_value) {
			if (!in_array($attr_key, $attributesBlackList) && isset($attr_value)) {
				echo ' ' . h($attr_key) . '="' . h($attr_value) . '"';
			}
		}
		if (in_array($attributes['type'], ['checkbox', 'radio']) && $attributes['checked'] === TRUE) {
			echo ' checked="checked"';
		}
		if ($attributes['required'] === TRUE) {
			echo ' required="required"';
		}
		if ($attributes['disabled'] === TRUE) {
			echo ' disabled="disabled"';
		}
		echo ' />';
		return ob_get_clean();
	}

	/**
	 *	Render a field label.
	 *	@param $fieldName	string	The name of the field.
	 *	@param $attributes	array	All other options, most of which will appear in on the input tag.
	 */
	function text($fieldName, $attributes = array()) {
		$attributes['type'] = 'text';
		return $this->input($fieldName, $attributes);
	}

	function textarea($fieldName, $attributes = array()) {
		ob_start();

		# Set defaults.
		$attributes += [
			'id' => NULL,
			'label' => FALSE,
			'value' => NULL,
			'required' => FALSE,
			'class' => '',
		];
		# These attributes should not appear in the <textarea> tag.
		$attributesBlackList = [
			'name',
			'label',
			'value',
			'required',
		];

		if ($attributes['required']) {
			$attributes['class'] .= ' required';
		}

		if (!empty($attributes['label'])) {
			echo $this->label($attributes['label'], $attributes['id']);
		}?>
		<textarea name="<?php echo $fieldName?>"<?php

		foreach ($attributes as $attr_key => $attr_value) {
			if (!empty($attr_value) && !in_array($attr_key, $attributesBlackList)) {
				echo ' ' . h($attr_key) . '="' . h($attr_value) . '"';
			}
		}
		if ($attributes['required'] === TRUE) {
			echo ' required="required"';
		}?>><?php echo (!empty($attributes['value'])) ? $attributes['value'] : ''?></textarea><?php
		return ob_get_clean();
	}


	/**
	 *	Render a select menu.
	 *	@param $fieldName	string	The name of the field.
	 *	@param $options		array	An associative array of key value pairs to populate the select options.
	 *	@param $attributes	array	All other options, most of which will appear in on the select tag.
	 */
	function select($fieldName, $options = [], $attributes = []) {
		ob_start();

		# Set defaults.
		$attributes += [
			'id' => NULL,
			'label' => FALSE,
			'selected' => FALSE,
			'empty' => FALSE,
			'empty_label' => NULL,
			'required' => FALSE,
			'class' => '',
			'multiple' => FALSE,
		];

		# These attributes should not appear in the <select> tag.
		$attributesBlackList = [
			'name',
			'label',
			'selected',
			'empty',
			'empty_label',
			'required',
		];

		if ($attributes['required']) {
			$attributes['class'] .= ' required';
		}

		if (!empty($attributes['label'])) {
			echo $this->label($attributes['label'], $attributes['id']);
		}
		?><select name="<?php echo $fieldName . (!empty($attributes['multiple']) ? '[]' : '')?>"<?php
		$this->render_element_attributes($attributes, $attributesBlackList);
		if ($attributes['required'] === TRUE) {
			echo ' required="required"';
		}?>><?php

			# If allowed, show an empty default option first.
			if ($attributes['empty'] === TRUE) {
				echo '<option value="">' . ((!empty($attributes['empty_label'])) ? $attributes['empty_label'] : '') . '</option>';
			}

			$this->render_select_options($options, $attributes);
			?>
		</select><?php
		return ob_get_clean();
	}

	private function render_select_options($options, $attributes = []) {
		foreach ($options as $key => $value) {

			$option_attributes = [];
			if (is_array($value) && !empty($value['label']) && !empty($value['attributes'])) {
				$option_attributes = $value['attributes'];
				$value = $value['label'];
			}

			if (is_array($value) && empty($value['label']) && empty($value['attributes'])) {
				echo '<optgroup label="' . h($key) . '"';
				$this->render_element_attributes($option_attributes);
				echo '>';
					$this->render_select_options($value, $attributes);
				echo '</optgroup>';
			} else {
				$sel = (!empty($attributes['selected']) && ((!empty($attributes['multiple']) && in_array($key, $attributes['selected'])) || (empty($attributes['multiple']) && $attributes['selected'] == $key))) ? ' selected="selected"' : '';
				echo '<option value="' . $key . '"' . $sel;
				$this->render_element_attributes($option_attributes);
				echo '>' . $value . '</option>';
			}
		}
	}

	private function render_element_attributes($attributes, $attributesBlackList = []) {
		foreach ($attributes as $attr_key => $attr_value) {
			if (!in_array($attr_key, $attributesBlackList) && !empty($attr_value)) {
				echo ' ' . h($attr_key) . '="' . h($attr_value) . '"';
			}
		}
	}


	/**
	 *	Render a select menu.
	 *	@param $fieldName	string	The name of the field.
	 *	@param $options		array	An associative array of key value pairs to populate the select options.
	 *	@param $attributes	array	All other options, most of which will appear in on the select tag.
	 */
	function checkboxes($fieldName, $options = [], $attributes = []) {
		ob_start();

		# Set defaults.
		$attributes += [
			'id' => NULL,
			'label' => FALSE,
			'selected' => [],
			'required' => FALSE,
			'class' => '',
			'container-class' => '',
			'before' => '',
			'between' => '',
			'after' => '',
			'disabled' => FALSE,
		];

		if (!empty($attributes['label'])) {
			echo $this->label($attributes['label'], $attributes['id']);
		}

		echo $attributes['before'];

		$options_count = count($options);
		foreach ((array) $options as $key => $value) {
			$key = strip_tags($key);
			$check_id = md5($attributes['id'] . '-' . $key . '-' . $value);
			echo ($this->bootstrap === 3) ? '<div class="checkbox' . (!empty($attributes['container-class']) ? ' ' . $attributes['container-class'] : '') . '">' : '';
			echo ($this->bootstrap === 4) ? '<div class="form-check' . (!empty($attributes['container-class']) ? ' ' . $attributes['container-class'] : '') . '">' : '';
				echo $this->input($fieldName . ($options_count > 1 ? '[]' : ''), [
					'id' => $check_id,
					'type' => 'checkbox',
					'value' => $key,
					'checked' => (in_array($key, (array) $attributes['selected'])),
					'class' => $attributes['class'],
					'disabled' => ($attributes['disabled']),
				]);
				echo $this->label($value, $check_id, (($this->bootstrap === 4) ? 'form-check-label' : ''));
				echo $attributes['between'];
			echo (in_array($this->bootstrap, [3, 4])) ? '</div>' : '';
		}
		echo $attributes['after'];
		return ob_get_clean();
	}


	function radios($fieldName, $options = array(), $attributes = array()) {
		ob_start();

		# Set defaults.
		$attributes += [
			'id' => NULL,
			'label' => FALSE,
			'selected' => NULL,
			'required' => FALSE,
			'class' => '',
			'container-class' => '',
			'before' => '',
			'between' => '',
			'after' => '',
			'disabled' => FALSE,
		];

		if (!empty($attributes['label'])) {
			echo $this->label($attributes['label'], $attributes['id']);
		}

		echo $attributes['before'];

		$options_count = count($options);
		$options_out = 1;
		foreach ((array) $options as $key => $value) {
			$key = strip_tags($key);
			$check_id = md5($attributes['id'] . '-' . $key . '-' . $value);
			echo ($this->bootstrap === 3) ? '<div class="radio' . (!empty($attributes['container-class']) ? ' ' . $attributes['container-class'] : '') . '">' : '';
			echo ($this->bootstrap === 4) ? '<div class="form-check' . (!empty($attributes['container-class']) ? ' ' . $attributes['container-class'] : '') . '">' : '';
				echo $this->input($fieldName, [
					'id' => $check_id,
					'type' => 'radio',
					'value' => $key,
					'checked' => (isset($attributes['selected']) && $key == $attributes['selected']),
					'required' => ($attributes['required'] === TRUE && $options_out == 1),
					'class' => $attributes['class'],
					'disabled' => ($attributes['disabled']),
				]);
				echo $this->label($value, $check_id, (($this->bootstrap === 4) ? 'form-check-label' : ''));
				echo $attributes['between'];
			echo (in_array($this->bootstrap, [3, 4])) ? '</div>' : '';
			$options_out++;
		}
		echo $attributes['after'];
		return ob_get_clean();
	}

}