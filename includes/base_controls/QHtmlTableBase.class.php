<?php
	/**
	 * <p>This control is used to display a simple html table.
	 *
	 * <p>The control itself will display things based off of an array of objects that gets set as the "Data Source".
	 * It is particularly useful when combined with the Class::LoadArrayByXXX() functions or the Class::LoadAll()
	 * that is generated by the CodeGen framework, or when combined with custom Class ArrayLoaders that you define
	 * youself, but who's structure is based off of the CodeGen framework.</p>
	 *
	 * <p>For each item in a datasource's Array, a row (&lt;tr&gt;) will be generated.
	 * You can define any number of QHtmlTableColumns which will result in a &lt;td&gt; for each row.
	 * Using the QHtmlTableColumn's Accessor property, you can specify how the data for each cell should be
	 * fetched from the datasource.</p>
	 *
	 * @package Controls
	 * @property string         $Caption          	  string to use as the caption of the table
	 * @property string         $RowCssClass          class to be given to the row tag
	 * @property string         $AlternateRowCssClass class to be given to each alternate row tag
	 * @property string         $HeaderRowCssClass    class to be given the header row
	 * @property boolean        $ShowHeader           true to show the header row
	 * @property boolean        $ShowFooter           true to show the footer row
	 * @property boolean        $RenderColumnTags     true to include col tags in the table output
	 * @property boolean        $HideIfEmpty          true to completely hide the table if there is no data, vs. drawing the table with no rows.
	 * @property-write Callable $RowParamsCallback    Set to a callback function to fetch custom attributes for row tags.
	 * @property-read integer 	$CurrentRowIndex      The visual index of the row currently being drawn.
	 * @throws QCallerException
	 *
	 */
	abstract class QHtmlTableBase extends QPaginatedControl {
		/** @var QAbstractHtmlTableColumn[] */
		protected $objColumnArray = [];

		/** @var string|null CSS class to be applied to for even rows */
		protected $strRowCssClass = null;
		/** @var string|null CSS class to be applied to for odd rows */
		protected $strAlternateRowCssClass = null;
		/** @var string|null CSS class to be applied to the header row */
		protected $strHeaderRowCssClass = null;
		/** @var bool Show the table header or not? */
		protected $blnShowHeader = true;
		/** @var bool Show the table footer or not? */
		protected $blnShowFooter = false;
		/** @var bool Column tags have to be rendered or not? */
		protected $blnRenderColumnTags = false;
		/** @var string|null Table caption, if applicable */
		protected $strCaption = null;
		/** @var bool When set, the table is hidden/not rendered when the data source is empty */
		protected $blnHideIfEmpty = false;

		/** @var integer */
		protected $intHeaderRowCount = 1;
		/** @var  integer Used during rendering to report which header row is being drawn in a multi-row header. */
		protected $intCurrentHeaderRowIndex;

		/** @var  integer Used during rendering to report which visible row is being drawn. */
		protected $intCurrentRowIndex;

		/** @var  callable */
		protected $objRowParamsCallback;

		/**
		 * Constructor method
		 *
		 * @param QControl|QControlBase|QForm $objParentObject
		 * @param null                        $strControlId
		 *
		 * @throws Exception
		 * @throws QCallerException
		 */
		public function __construct($objParentObject, $strControlId = null)	{
			try {
				parent::__construct($objParentObject, $strControlId);
			} catch (QCallerException  $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
		}

		/**
		 * Nothing to parse in current implementation
		 */
		public function ParsePostData() {
			if ($this->objColumnArray) {
				foreach($this->objColumnArray as $objColumn) {
					$objColumn->ParsePostData();
				}
			}
		}

		/**
		 * Add an Index column and return it.
		 * Index columns assume that each data item is an array, and mixIndex is an offset in the array.
		 *
		 * @param string  $strName        column name
		 * @param mixed   $mixIndex       the index to use to access the cell date. i.e. $item[$index]
		 * @param integer $intColumnIndex column position
		 *
		 * @return QHtmlTableIndexedColumn
		 */
		public function CreateIndexedColumn($strName = '', $mixIndex = null, $intColumnIndex = -1) {
			if (is_null($mixIndex)) {
				$mixIndex = count($this->objColumnArray);
			}
			$objColumn = new QHtmlTableIndexedColumn($strName, $mixIndex);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a property column and return it. The assumption is that each row's data is an object.
		 *
		 * @param string  $strName        name of column
		 * @param string  $strProperty    property to use to get the cell data. i.e. $item->$property
		 * @param integer $intColumnIndex column position
		 * @param object  $objBaseNode    a query node from which the property descends, if you are using the sorting capabilities
		 *
		 * @return QHtmlTablePropertyColumn
		 */
		public function CreatePropertyColumn($strName, $strProperty, $intColumnIndex = -1, $objBaseNode = null) {
			$objColumn = new QHtmlTablePropertyColumn($strName, $strProperty, $objBaseNode);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * @param string $strName
		 * @param mixed $objNodes
		 * @param int $intColumnIndex
		 * @return QHtmlTableNodeColumn
		 * @throws Exception
		 * @throws QInvalidCastException
		 */
		public function CreateNodeColumn($strName, $objNodes, $intColumnIndex = -1) {
			$objColumn = new QHtmlTableNodeColumn($strName, $objNodes);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a callable column and return it.
		 *
		 * @param string         $strName        column name
		 * @param callable|array $objCallable    a callable object. Note that this can be an array.
		 * @param integer        $intColumnIndex column position
		 *
		 * @return QHtmlTableCallableColumn
		 */
		public function CreateCallableColumn($strName, $objCallable, $intColumnIndex = -1) {
			$objColumn = new QHtmlTableCallableColumn($strName, $objCallable);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a virtual attribute column.
		 *
		 * @param $strName
		 * @param $strAttribute
		 * @param $intColumnIndex
		 * @return QVirtualAttributeColumn
		 */
		public function CreateVirtualAttributeColumn ($strName, $strAttribute, $intColumnIndex = -1) {
			$objColumn = new QVirtualAttributeColumn($strName, $strAttribute);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a link column.
		 *
		 * @param string $strName Column name to be displayed in the table header.
		 * @param null|string|array $mixText The text to display as the label of the anchor, a callable callback to get the text,
		 *   a string that represents a property chain or a multi-dimensional array, or an array that represents the same. Depends on
		 *   what time of row item is passed.
		 * @param null|string|array|QControlProxy $mixDestination The text representing the destination of the anchor, a callable callback to get the destination,
		 *   a string that represents a property chain or a multi-dimensional array, or an array that represents the same,
		 *   or a QControlProxy. Depends on what type of row item is passed.
		 * @param null|string|array $getVars An array of key=>value pairs to use as the GET variables in the link URL,
		 *   or in the case of a QControlProxy, possibly a string to represent the action parameter. In either case, each item
		 *   can be a property chain, an array index list, or a callable callback as specified above.  If the destination is a
		 *   QControlProxy, this would be what to use as the action parameter.
		 * @param null|array $tagAttributes An array of key=>value pairs to use as additional attributes in the tag.
		 *   For example, could be used to add a class or an id to each tag.
		 * @param bool $blnAsButton Only used if this is drawing a QControlProxy. Will draw the proxy as a button.
		 * @param int $intColumnIndex
		 * @return QHtmlTableLinkColumn
		 * @throws QInvalidCastException
		 */
		public function CreateLinkColumn ($strName,
										  $mixText,
										  $mixDestination = null,
										  $getVars = null,
										  $tagAttributes = null,
										  $blnAsButton = false,
										  $intColumnIndex = -1) {
			$objColumn = new QHtmlTableLinkColumn($strName,
				$mixText,
				$mixDestination,
				$getVars,
				$tagAttributes,
				$blnAsButton);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}


		/**
		 * Move the named column to the given position
		 *
		 * @param string  $strName        column name
		 * @param integer $intColumnIndex new position
		 * @param string  $strNewName     new column name
		 *
		 * @return QAbstractHtmlTableColumn
		 */
		public function MoveColumn($strName, $intColumnIndex = -1, $strNewName = null) {
			$col = $this->RemoveColumnByName($strName);
			$this->AddColumnAt($intColumnIndex, $col);
			if ($strNewName !== null) {
				$col->Name = $strNewName;
			}
			return $col;
		}

		/**
		 * Rename a named column
		 *
		 * @param string $strOldName
		 * @param string $strNewName
		 *
		 * @return QAbstractHtmlTableColumn
		 */
		public function RenameColumn($strOldName, $strNewName) {
			$col = $this->GetColumnByName($strOldName);
			$col->Name = $strNewName;
			return $col;
		}

		/**
		 * Add a column to the end of the column array.
		 *
		 * @param QAbstractHtmlTableColumn $objColumn
		 *
		 * @return QAbstractHtmlTableColumn
		 */
		public function AddColumn(QAbstractHtmlTableColumn $objColumn) {
			$this->AddColumnAt(-1, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a column at the given position. All column adds bottle neck through here
		 * so that subclasses can reliably override the column add process if needed.
		 * Use AddColumn to add a column to the end.
		 *
		 * @param integer                    $intColumnIndex column position. -1 to add to the end.
		 * @param QAbstractHtmlTableColumn $objColumn
		 *
		 * @throws QInvalidCastException
		 */
		public function AddColumnAt($intColumnIndex, QAbstractHtmlTableColumn $objColumn) {
			try {
				$intColumnIndex = QType::Cast($intColumnIndex, QType::Integer);
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			$this->blnModified = true;
			$objColumn->_ParentTable = $this;
			if ($intColumnIndex < 0 || $intColumnIndex > count($this->objColumnArray)) {
				$this->objColumnArray[] = $objColumn;
			}
			elseif ($intColumnIndex == 0) {
				$this->objColumnArray = array_merge(array($objColumn), $this->objColumnArray);
			} else {
				$this->objColumnArray = array_merge(array_slice($this->objColumnArray, 0, $intColumnIndex),
													array($objColumn),
													array_slice($this->objColumnArray, $intColumnIndex));
			}
		}

		/**
		 * Removes a column from the table
		 *
		 * @param int $intColumnIndex 0-based index of the column to remove
		 *
		 * @return QAbstractHtmlTableColumn the removed column
		 * @throws QIndexOutOfRangeException|QInvalidCastException
		 */
		public function RemoveColumn($intColumnIndex) {
			$this->blnModified = true;
			try {
				$intColumnIndex = QType::Cast($intColumnIndex, QType::Integer);
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			if ($intColumnIndex < 0 || $intColumnIndex > count($this->objColumnArray)) {
				throw new QIndexOutOfRangeException($intColumnIndex, "RemoveColumn()");
			}

			$col = $this->objColumnArray[$intColumnIndex];
			array_splice($this->objColumnArray, $intColumnIndex, 1);
			return $col;
		}

		/**
		 * Removes the column by column id. Assumes the ids are unique.
		 *
		 * @param $strId
		 */
		public function RemoveColumnById($strId) {
			if ($this->objColumnArray && ($count = count($this->objColumnArray))) {
				for ($i = 0; $i < $count; $i++) {
					if ($this->objColumnArray[$i]->Id === $strId) {
						$this->RemoveColumn($i);
						return;
					}
				}
			}
		}

		/**
		 * Remove the first column that has the given name
		 *
		 * @param string $strName name of the column to remove
		 *
		 * @return QAbstractHtmlTableColumn the removed column or null of no column with the given name was found
		 */
		public function RemoveColumnByName($strName) {
			$this->blnModified = true;
			for ($intIndex = 0; $intIndex < count($this->objColumnArray); $intIndex++) {
				if ($this->objColumnArray[$intIndex]->Name == $strName) {
					$col = $this->objColumnArray[$intIndex];
					array_splice($this->objColumnArray, $intIndex, 1);
					return $col;
				}
			}
			return null;
		}

		/**
		 * Remove all the columns that have the given name
		 *
		 * @param string $strName name of the columns to remove
		 *
		 * @return QAbstractHtmlTableColumn[] the array of columns removed
		 */
		public function RemoveColumnsByName($strName/*...*/) {
			return $this->RemoveColumns(func_get_args());
		}

		/**
		 * Remove all the columns that have any of the names in $strNamesArray
		 *
		 * @param string[] $strNamesArray names of the columns to remove
		 *
		 * @return QAbstractHtmlTableColumn[] the array of columns removed
		 */
		public function RemoveColumns($strNamesArray) {
			$this->blnModified = true;
			$kept = array();
			$removed = array();
			foreach ($this->objColumnArray as $objColumn) {
				if (array_search($objColumn->Name, $strNamesArray) === false) {
					$kept[] = $objColumn;
				} else {
					$removed[] = $objColumn;
				}
			}
			$this->objColumnArray = $kept;
			return $removed;
		}

		/**
		 * Remove all columns from the grid.
		 */
		public function RemoveAllColumns() {
			$this->blnModified = true;
			$this->objColumnArray = array();
		}

		/**
		 * Hide all columns without removing them from the grid. They will not display in the html, but they will
		 * still be part of the form state.
		 */
		public function HideAllColumns() {
			foreach ($this->objColumnArray as $objColumn) {
				$objColumn->Visible = false;
			}
			$this->blnModified = true;
		}

		/**
		 * Show all columns.
		 */
		public function ShowAllColumns() {
			foreach ($this->objColumnArray as $objColumn) {
				$objColumn->Visible = true;
			}
			$this->blnModified = true;
		}



		/**
		 * Returns all columns in the table
		 *
		 * @return QAbstractHtmlTableColumn[]
		 */
		public function GetAllColumns() {
			return $this->objColumnArray;
		}

		/**
		 * Get the column at the given index, or null if the index is not valid
		 *
		 * @param integer $intColumnIndex
		 * @param boolean $blnVisible true to only count the visible columns
		 *
		 * @return QAbstractHtmlTableColumn
		 */
		public function GetColumn($intColumnIndex, $blnVisible = false) {
			if (!$blnVisible) {
				if (array_key_exists($intColumnIndex, $this->objColumnArray)) {
					return $this->objColumnArray[$intColumnIndex];
				}
			} else {
				$i = 0;
				foreach ($this->objColumnArray as $objColumn) {
					if ($objColumn->Visible) {
						if ($i == $intColumnIndex) {
							return $objColumn;
						}
						$i++;
					}
				}
			}
			return null;
		}

		/**
		 * Get the first column that has the given name, or null if a column with the given name does not exist
		 *
		 * @param string $strName column name
		 *
		 * @return QAbstractHtmlTableColumn
		 */
		public function GetColumnByName($strName) {
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Name == $strName)
					return $objColumn;
			return null;
		}

		/**
		 * @param $strId
		 *
		 * @return null|QAbstractHtmlTableColumn
		 */
		public function GetColumnById($strId) {
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Id === $strId)
					return $objColumn;
			return null;
		}


		/**
		 * Get the first column that has the given name, or null if a column with the given name does not exist
		 *
		 * @param string $strName column name
		 *
		 * @return QAbstractHtmlTableColumn
		 */
		public function GetColumnIndex($strName) {
			$intIndex = -1;
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn) {
				++$intIndex;
				if ($objColumn->Name == $strName)
					return $intIndex;
			}
			return $intIndex;
		}

		/**
		 * Get all the columns that have the given name
		 *
		 * @param string $strName column name
		 *
		 * @return QAbstractHtmlTableColumn[]
		 */
		public function GetColumnsByName($strName) {
			$objColumnArrayToReturn = array();
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Name == $strName)
					array_push($objColumnArrayToReturn, $objColumn);
			return $objColumnArrayToReturn;
		}

		/**
		 * Returns the HTML for the header row, including the <<tr>> and <</tr>> tags
		 */
		protected function GetHeaderRowHtml() {
			$strToReturn = '';
			for ($i = 0; $i < $this->intHeaderRowCount; $i++) {
				$this->intCurrentHeaderRowIndex = $i;

				$strCells = '';
				if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn) {
					$strCells .= $objColumn->RenderHeaderCell();
				}
				$strToReturn .= QHtml::RenderTag('tr', $this->GetHeaderRowParams(), $strCells);
			}

			return $strToReturn;
		}

		/**
		 * Returns a key=>val array of parameters to insert inside of the header row's <<tr>> tag.
		 *
		 * @return array
		 */
		protected function GetHeaderRowParams () {
			$strParamArray = array();
			if ($strClass = $this->strHeaderRowCssClass) {
				$strParamArray['class'] = $strClass;
			}
			return $strParamArray;		
		}
		
		
		/**
		 * Get the html for the row, from the opening <<tr>> to the closing <</tr>> inclusive
		 *
		 * @param object  $objObject          Current object from the DataSource array
		 * @param integer $intCurrentRowIndex Current visual row index being output.
		 *                                    This is NOT the index of the data source,
		 *                                    only the visual row number currently on screen.
		 *
		 * @return string
		 * @throws Exception
		 * @throws QCallerException
		 */
		protected function GetDataGridRowHtml($objObject, $intCurrentRowIndex) {
			$strCells = '';
			foreach ($this->objColumnArray as $objColumn) {
				try {
					$strCells .= $objColumn->RenderCell($objObject);
				} catch (QCallerException $objExc) {
					$objExc->IncrementOffset();
					throw $objExc;
				}
			}

			return QHtml::RenderTag('tr', $this->GetRowParams($objObject, $intCurrentRowIndex), $strCells);
		}
		
		/**
		 * Returns a key/val array of params that will be inserted inside the <<tr>> tag for this row. 
		 * 
		 * Handles  class, style, and id by default. Override to add additional types of parameters,
		 * like an 'onclick' paramater for example. No checking is done on these params, the raw strings are output.
		 * 
		 * @param mixed $objObject	The object of data being used as the row.
		 * @param integer $intCurrentRowIndex
		 * @return array	Array of key/value pairs that will be used as attributes for the row tag.
		 */
		protected function GetRowParams ($objObject, $intCurrentRowIndex) {
			$strParamArray = array();
			if ($this->objRowParamsCallback) {
				$strParamArray = call_user_func($this->objRowParamsCallback, $objObject, $intCurrentRowIndex);
			}
			if ($strClass = $this->GetRowClass ($objObject, $intCurrentRowIndex)) {
				$strParamArray['class'] = $strClass;
			}
			
			if ($strId = $this->GetRowId ($objObject, $intCurrentRowIndex)) {
				$strParamArray['id'] = $strId;
			}
			
			if ($strStyle = $this->GetRowStyle ($objObject, $intCurrentRowIndex)) {
				$strParamArray['style'] = $strStyle;
			}
			return $strParamArray;		
		}
		
		
		/**
		 * Return the html row id.
		 * Override this to give the row an id.
		 *
		 * @param object  $objObject   object associated with this row
		 * @param integer $intRowIndex index of the row
		 *
		 * @return null
		 */
		protected function GetRowId ($objObject, $intRowIndex) {
			return null;
		}

		/**
		 * Return the style string for this row.
		 *
		 * @param object  $objObject
		 * @param integer $intRowIndex
		 *
		 * @return null
		 */
		protected function GetRowStyle ($objObject, $intRowIndex) {
			return null;
		}
		
		/**
		 * Return the class string of this row.
		 *
		 * @param object  $objObject
		 * @param integer $intRowIndex
		 *
		 * @return null
		 */
		protected function GetRowClass ($objObject, $intRowIndex) {
			if (($intRowIndex % 2) == 1 && $this->strAlternateRowCssClass) {
				return $this->strAlternateRowCssClass;
			} else if ($this->strRowCssClass) {
				return $this->strRowCssClass;
			}
			else {
				return null;
			}
		}
		
		/**
		 * Override to return the footer row html
		 */
		protected function GetFooterRowHtml() { }
		
		/**
		 * Returns column tags. Only called if blnRenderColumnTags is true.
		 * @return string Column tag html
		 */
		protected function GetColumnTagsHtml() {
			$strToReturn = '';
			$len = count($this->objColumnArray);
			$i = 0;
			while ($i < $len) {
				$objColumn = $this->objColumnArray[$i];
				if ($objColumn->Visible) {
					$strToReturn .= $objColumn->RenderColTag() . _nl();
				}
				$i += $objColumn->Span;
			}
			return $strToReturn;
		}

		/**
		 * Returns the caption string which can be used for the table.
		 *
		 * @return string
		 */
		protected function RenderCaption() {
			$strHtml = '';
			if ($this->strCaption) {
				$strHtml .= '<caption>' . QApplication::HtmlEntities($this->strCaption) . '</caption>' . _nl();
			}
			return $strHtml;
		}

		/**
		 * Return the html for the table.
		 *
		 * @return string
		 */
		protected function GetControlHtml() {
			$this->DataBind();

			if (empty ($this->objDataSource) && $this->blnHideIfEmpty) {
				$this->objDataSource = null;
				return '';
			}


			$strHtml = $this->RenderCaption();

			// Column tags (if applicable)
			if ($this->blnRenderColumnTags) {
				$strHtml .= $this->GetColumnTagsHtml();
			}
			
			// Header Row (if applicable)
			if ($this->blnShowHeader) {
				$strHtml .= QHtml::RenderTag ('thead', null, $this->GetHeaderRowHtml());
			}

			// Footer Row (if applicable)
			if ($this->blnShowFooter) {
				$strHtml .=  QHtml::RenderTag ('tfoot', null, $this->GetFooterRowHtml());
			}

			// DataGrid Rows
			$strRows = '';
			$this->intCurrentRowIndex = 0;
			if ($this->objDataSource) {
				foreach ($this->objDataSource as $objObject) {
					$strRows .= $this->GetDataGridRowHtml($objObject, $this->intCurrentRowIndex);
					$this->intCurrentRowIndex++;
				}
			}
			$strHtml .= QHtml::RenderTag('tbody', null, $strRows);

			$strHtml = $this->RenderTag('table', null, null, $strHtml);

			$this->objDataSource = null;
			return $strHtml;
		}

		/**
		 * Preserialize the columns, since some columns might have references to the form.
		 */
		public function Sleep() {
			if ($this->objColumnArray) {
				foreach ($this->objColumnArray as $objColumn) {
					$objColumn->Sleep();
				}
			}
			$this->objRowParamsCallback = QControl::SleepHelper($this->objRowParamsCallback);
			parent::Sleep();
		}

		/**
		 * Restore references.
		 *
		 * @param QForm $objForm
		 */
		public function Wakeup(QForm $objForm) {
			parent::Wakeup($objForm);
			$this->objRowParamsCallback = QControl::WakeupHelper($objForm, $this->objRowParamsCallback);
			if ($this->objColumnArray) {
				foreach ($this->objColumnArray as $objColumn) {
					$objColumn->Wakeup($objForm);
				}
			}
		}

		/**
		 * PHP magic method
		 *
		 * @param string $strName
		 *
		 * @return bool|int|mixed|null
		 * @throws Exception
		 * @throws QCallerException
		 */
		public function __get($strName) {
			switch ($strName) {
				case 'RowCssClass':
					return $this->strRowCssClass;
				case 'AlternateRowCssClass':
					return $this->strAlternateRowCssClass;
				case 'HeaderRowCssClass':
					return $this->strHeaderRowCssClass;
				case 'ShowHeader':
					return $this->blnShowHeader;
				case 'ShowFooter':
					return $this->blnShowFooter;
				case 'RenderColumnTags':
					return $this->blnRenderColumnTags;
				case 'Caption':
					return $this->strCaption;
				case 'HeaderRowCount':
					return $this->intHeaderRowCount;
				case 'CurrentHeaderRowIndex':
					return $this->intCurrentHeaderRowIndex;
				case 'HideIfEmpty':
					return $this->blnHideIfEmpty;
				case 'CurrentRowIndex':
					return $this->intCurrentRowIndex;

				default:
					try {
						return parent::__get($strName);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		/**
		 * PHP magic method
		 *
		 * @param string $strName
		 * @param string $mixValue
		 *
		 * @return mixed|void
		 * @throws Exception
		 * @throws QCallerException
		 * @throws QInvalidCastException
		 */
		public function __set($strName, $mixValue) {
			switch ($strName) {
				case "RowCssClass":
					try {
						$this->strRowCssClass = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "AlternateRowCssClass":
					try {
						$this->strAlternateRowCssClass = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "HeaderRowCssClass":
					try {
						$this->strHeaderRowCssClass = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "ShowHeader":
					try {
						$this->blnShowHeader = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "ShowFooter":
					try {
						$this->blnShowFooter = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "RenderColumnTags":
					try {
						$this->blnRenderColumnTags = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "Caption":
					try {
						$this->strCaption = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "HeaderRowCount":
					try {
						$this->intHeaderRowCount = QType::Cast($mixValue, QType::Integer);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "HideIfEmpty":
					try {
						$this->blnHideIfEmpty = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "RowParamsCallback":
					try {
						assert (is_callable($mixValue));
						$this->objRowParamsCallback = $mixValue;
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}


				default:
					try {
						parent::__set($strName, $mixValue);
						break;
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		/**
		 * Returns an description of the options available to modify by the designer for the code generator.
		 *
		 * @return QModelConnectorParam[]
		 */
		public static function GetModelConnectorParams() {
			return array_merge(parent::GetModelConnectorParams(), array(
				new QModelConnectorParam (get_called_class(), 'RowCssClass', 'Css class given to each row', QType::String),
				new QModelConnectorParam (get_called_class(), 'AlternateRowCssClass', 'Css class given to every other row', QType::String),
				new QModelConnectorParam (get_called_class(), 'HeaderRowCssClass', 'Css class given to the header rows', QType::String),
				new QModelConnectorParam (get_called_class(), 'ShowHeader', 'Whether or not to show the header. Default is true.', QType::Boolean),
				new QModelConnectorParam (get_called_class(), 'ShowFooter', 'Whether or not to show the footer. Default is false.', QType::Boolean),
				new QModelConnectorParam (get_called_class(), 'RenderColumnTags', 'Whether or not to render html column tags for the columns. Column tags are only needed in special situations. Default is false.', QType::Boolean),
				new QModelConnectorParam (get_called_class(), 'Caption', 'Text to print in the caption tag of the table.', QType::String),
				new QModelConnectorParam (get_called_class(), 'HideIfEmpty', 'Whether to draw nothing if there is no data, or draw the table tags with no cells instead. Default is to drag the table tags.', QType::Boolean)
			));
		}
	}
