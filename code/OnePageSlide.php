<?php

class OnePageSlide extends DataExtension {

	private static $db = array(
		'BackgroundColor' => 'Varchar',
		'HeadingColor' => 'Varchar',
		'TextColor' => 'Varchar',
		'AdditionalCSSClass' => 'Varchar'
	);

    private static $has_one = array(
		'BackgroundImage' => 'Image'
	);

	private static $background_color_palette = array(
		'#fff' => '#fff',
		'#444' => '#444',
		'#000' => '#000'
	);
	private static $heading_color_palette = array(
		'#000' => '#000',
		'#fff' => '#fff'
	);
	private static $text_color_palette = array(
		'#000' => '#000',
		'#fff' => '#fff'
	);

	/**
	 * limit the generated form fields to slides (direct children of a OnePageHolder)
	 * @var bool
	 */
	private static $use_only_on_onepage_slides = false;

	/**
	 * do not require colors to be set
	 * @var bool
	 */
	private static $colors_can_be_empty = false;

	/**
	 * @inheritdoc
	 */
	public function updateFieldLabels(&$labels)
	{
		$labels = parent::updateFieldLabels($labels);

		$labels['Title'] = _t('OnePageSlide.db_Title', 'Title');
		$labels['BackgroundColor'] = _t('OnePageSlide.db_BackgroundColor', 'Background Color');
		$labels['HeadingColor'] = _t('OnePageSlide.db_HeadingColor', 'Heading Color');
		$labels['TextColor'] = _t('OnePageSlide.db_TextColor', 'Text Color');
		$labels['AdditionalCSSClass'] = _t('OnePageSlide.db_AdditionalCSSClass', 'Additional CSS class');

		$labels['BackgroundImage'] = _t('OnePageSlide.has_many_BackgroundImage', 'Background Image');
	}


	/**
	 * @inheritdoc
	 */
	public function updateCMSFields(FieldList $fields) {

		if (Config::inst()->get($this->class, 'use_only_on_onepage_slides')
			&& !$this->owner->isOnePageSlide()) {
			return;
		}

		$image = UploadField::create('BackgroundImage',$this->owner->fieldLabel('BackgroundImage'))
			->setAllowedFileCategories('image')
			->setAllowedMaxFileNumber(1);
		if ($this->owner->hasMethod('getRootFolderName')) {
			$image->setFolderName($this->owner->getRootFolderName());
		}

		$colorFields = array(
			'BackgroundColor' => 'background_color_palette',
			'HeadingColor' => 'heading_color_palette',
			'TextColor' => 'text_color_palette'
		);

		$layout = $fields->findOrMakeTab('Root.Layout',_t('OnePageSlide.TABLAYOUT', 'Layout'));
		$layout->push($image);

		foreach ($colorFields as $fieldName => $palette) {
			$layout->push($this->generateColorPalette($fieldName, $palette));
		}
		$layout->push(TextField::create('AdditionalCSSClass', $this->owner->fieldLabel('AdditionalCSSClass')));
	}

	protected function generateColorPalette($fieldName, $paletteSetting) {

		$palette = $this->owner->config()->get($paletteSetting)
			? $this->owner->config()->get($paletteSetting)
			: Config::inst()->get($this->class, $paletteSetting);
		
		$field = ColorPaletteField::create(
			$fieldName,
			$this->owner->fieldLabel($fieldName),
			$palette
		);

		if (Config::inst()->get($this->class, 'colors_can_be_empty')) {
			$field= $field->setEmptyString('none');
		}

		return $field;
	}

	//@todo: if Parent is a OnePageHolder modify $Link to show to $Parent->Link() / #$URLSegment
	//@todo: if Parent is a OnePageHolder disable ShowInMenus
	//@todo: don't show slide in google sitempap

	/**
	 * @todo: use customCSS?
	 * @return string
	 */
	public function getOnePageSlideStyle(){
		$style = '';

		$style .= $this->owner->BackgroundColor
			? 'background-color: ' . $this->owner->BackgroundColor . '; '
			: '';

		$style .= $this->owner->TextColor
			? ' color: ' . $this->owner->TextColor. ' !important; '
			: '';

		$this->owner->extend('updateOnePageSlideStyle', $style);

		return $style;

	}

	/**
	 * get's fired on ContentController::init()
	 *
	 * check if this is a OnePageSlide and redirect to parent if
	 *  - controller has no action
	 *  - request isn't an ajax request
	 */
	public function contentcontrollerInit(&$controller){
		if ($this->owner->isOnePageSlide()
				&& !$controller->urlParams['Action']
				&& !Director::is_ajax()) {
			$controller->redirect($this->owner->Parent()->Link('#'.$this->owner->URLSegment), 301);
		}
	}

	/**
	 * Udates RelativeLink()
	 *
	 * If no $action is given it changes /path/to/URLSegment into /path/to#URLSegment
	 *
	 * @param $base
	 * @param $action
	 */
	public function updateRelativeLink(&$base, &$action){
		if (!$action && $this->owner->isOnePageSlide()) {
//			$base = $this->owner->Parent()->RelativeLink('#' . $this->owner->URLSegment); //e.g. /home/#urlsegment :(
			$base = $this->owner->Parent()->RelativeLink($action) . '#' . $this->owner->URLSegment; // just /#urlsegment
		}
	}


	/**
	 * Checks, if the current page is a slide of a one-page by checking if the parent page is a OnePageHolder
	 * @return bool
	 */
	public function isOnePageSlide(){
		return ($this->owner->Parent() instanceof OnePageHolder);
	}

	/**
	 * renders the current page using the ClassName_onepage template,
	 * e.g. Page_onepage
	 *
	 * @return HTMLText
	 */
	public function getOnePageContent(){
		$templateName = SSViewer::get_templates_by_class($this->owner->Classname, '_onepage', 'SiteTree')
			?: 'Page_onepage';

		$controller = ModelAsController::controller_for($this->owner);

	    return $controller->renderWith($templateName);
	}
}

