<?php

namespace ADT\Components\AjaxSelect\Entities;

use ADT\Components\AjaxSelect;
use Nette\SmartObject;

abstract class AbstractEntity {

	use SmartObject;

	const DATA_ATTRIBUTE_NAME = 'data-ajax-select';

	const OPTION_QUERY = 'q';

	/** @var string */
	protected $name;

	/** @var array */
	protected $config;

	/** @var bool */
	protected $isDirty = TRUE;

	/** @var array */
	protected $options = [ ];

	/** @var AjaxSelect\Traits\AjaxServiceControlTrait|NULL */
	protected $control = NULL;

	/** @var mixed|NULL */
	protected $backValue = NULL;

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options) {
		$this->options = $options;
		$this->isDirty = TRUE;
		return $this;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function parseOptions(array $options) {
		array_walk_recursive($options, function (&$value) {
			if ($value === 'true') {
				$value = TRUE;
			} else if ($value === 'false') {
				$value = FALSE;
			}
		});
		return $this->setOptions($options);
	}

	/**
	 * @internal
	 * @param array $values
	 * @return bool[] $value => $isValid
	 */
	public abstract function areValidValues(array $values);

	/**
	 * @internal
	 * @param mixed $value
	 * @return bool
	 */
	public function isValidValue($value) {
		$areValid = $this->areValidValues([ $value ]);
		return !empty($areValid[$value]);
	}

	/**
	 * @internal
	 * @param int $limit
	 * @return array List of ids.
	 */
	public abstract function findValues($limit);

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public abstract function formatValues($values);

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public function formatJsonValues($values) {
		$result = [ ];
		$titles = $this->formatValues($values);

		foreach ($titles as $id => $item) {
			if (!is_array($item)) {
				$item = [
					'text' => $item,
				];
			}

			$item['id'] = $id;
			$result[] = $item;
		}

		return $result;
	}

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return $this
	 */
	public function set($option, $value) {
		$this->options[$option] = $value;
		$this->isDirty = TRUE;
		return $this;
	}

	/**
	 * @param string $option
	 * @param mixed|NULL $defaultValue
	 * @return mixed|NULL
	 */
	public function get($option, $defaultValue = NULL) {
		return isset($this->options[$option])
			? $this->options[$option]
			: $defaultValue;
	}

	/**
	 * @internal
	 * @return AjaxSelect\Interfaces\IAjaxServiceControl|NULL
	 */
	public function getControl() {
		return $this->control;
	}

	/**
	 * @internal
	 * @param AjaxSelect\Interfaces\IAjaxServiceControl $control
	 * @return $this
	 */
	public function setControl(AjaxSelect\Interfaces\IAjaxServiceControl $control) {
		$this->control = $control;
		return $this;
	}

	/**
	 * Serializes ajax data to HTML attribute.
	 * @return $this
	 */
	public function done() {
		$control = $this->getControl();

		if (/*$this->isDirty && */$control) { // TODO: set dirty = TRUE on value change
			/** @var \Nette\Application\UI\Presenter $presenter */
			$presenter = $control->lookup(\Nette\Application\UI\Presenter::class, FALSE);
			bd($presenter);
			if ($presenter) {
				$getItemsSignal = $this->config[AjaxSelect\DI\AjaxSelectExtension::CONFIG_GET_ITEMS_SIGNAL_NAME];
				$controlValue = $control->getValue();

				$data = [
					'url' => $presenter->link($getItemsSignal . '!', ['htmlId' => $control->getHtmlId()]),
					'initialItems' => $controlValue
						? $this->formatJsonValues(
							is_array($controlValue)
								? $controlValue
								: [ $controlValue ]
						)
						: [],
					'queryParam' => static::OPTION_QUERY,
					'entityName' => $this->getName(),
					'entityOptions' => $this->getOptions(),
				];

				if ($control instanceof AjaxSelect\Interfaces\IPromptControl) {
					$data['prompt'] = $control->translate($control->getPrompt());
				}

				if ($control instanceof AjaxSelect\Interfaces\IMultiSelectControl) {
					$data['multiple'] = TRUE;
				}

				$control->setAttribute(static::DATA_ATTRIBUTE_NAME, $data);
				$this->isDirty = FALSE;
			}
		}

		return $this;
	}

	/**
	 * @return AjaxSelect\Interfaces\IAjaxServiceControl|mixed|NULL
	 */
	public function back() {
		$this->done();

		return $this->backValue ?: $this->getControl();
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function setConfig(array $config) {
		$this->config = $config;
		return $this;
	}

	public function getConfig() {
		return $this->config;
	}

	public function getName() {
		return $this->name;
	}

	public function getOptions() {
		return $this->options;
	}

	public function setBackValue($value) {
		$this->backValue = $value;
		return $this;
	}

}
