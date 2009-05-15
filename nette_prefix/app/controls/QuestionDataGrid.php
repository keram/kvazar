<?php



class QuestionDataGrid extends DataGrid
{

	/**
	 * Renders table grid.
	 */
	public function renderGrid()
	{
		$dataSource = $this->dataSource;

		// paging
		$this->paginator->page = $this->page;
		$dataSource->applyLimit($this->paginator->length, $this->paginator->offset);

        // sorting
		$i = 1;
		parse_str($this->order, $list);
		foreach ($list as $field => $dir) {
			$dataSource->orderBy($field, $dir === 'a' ? dibi::ASC : dibi::DESC);
			$list[$field] = array($dir, $i++);
		}

		// render
		$template = $this->createTemplate();
		$template->rows = $dataSource->getIterator();
		$template->columns = $dataSource->getResult()->getColumnNames();
		$template->order = $list;
		$template->useAjax = $this->useAjax;
		$template->setFile(dirname(__FILE__) . '/question_grid.phtml');
		$template->registerFilter('Nettep\Templates\NCurlyBracketsFilter::invoke');
		$template->render();
	}

}
