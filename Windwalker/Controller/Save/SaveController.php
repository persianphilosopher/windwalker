<?php

namespace Windwalker\Controller\Save;

use Windwalker\Controller\Admin\AbstractItemController;
use Windwalker\Model\Exception\ValidateFailException;

/**
 * Class SaveController
 *
 * @since 1.0
 */
class SaveController extends AbstractItemController
{
	/**
	 * Property key.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Property urlVar.
	 *
	 * @var string
	 */
	protected $urlVar;

	/**
	 * Property recordId.
	 *
	 * @var
	 */
	protected $recordId;

	/**
	 * Property data.
	 *
	 * @var
	 */
	protected $data;

	/**
	 * Property table.
	 *
	 * @var
	 */
	protected $table;

	/**
	 * Property model.
	 *
	 * @var
	 */
	protected $model;

	/**
	 * Property lang.
	 *
	 * @var
	 */
	protected $lang;

	/**
	 * Constructor.
	 *
	 * @param \JInput          $input
	 * @param \JApplicationCms $app
	 * @param array            $config
	 */
	public function __construct(\JInput $input = null, \JApplicationCms $app = null, $config = array())
	{
		parent::__construct($input, $app, $config);

		$this->key    = \JArrayHelper::getValue($config, 'key');
		$this->urlVar = \JArrayHelper::getValue($config, 'urlVar');
	}

	/**
	 * prepare
	 *
	 * @return void
	 */
	protected function prepareExecute()
	{
		parent::prepareExecute();

		// Check for request forgeries.
		$this->checkToken();

		$this->lang     = \JFactory::getLanguage();
		$this->model    = $this->getModel();
		$this->table    = $this->model->getTable();
		$this->data     = $this->input->post->get('jform', array(), 'array');
		$this->context  = "$this->option.edit.$this->context";

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$this->key = $this->table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$this->urlVar = $this->key;
		}

		$this->recordId = $this->input->getInt($this->urlVar);

		// Populate the row id from the session.
		$this->data[$this->key] = $this->recordId;
	}

	/**
	 * execute
	 *
	 * @return mixed
	 */
	public function doExecute()
	{
		return $this->doSave();
	}

	/**
	 * doSave
	 *
	 * @return bool
	 */
	protected function doSave()
	{
		$data = $this->data;
		$key  = $this->key;

		try
		{
			// Access check.
			if (!$this->allowSave($this->data, $key))
			{
				throw new \Exception(\JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
			}

			// Validate the posted data.
			// Sometimes the form needs some posted data, such as for plugins and modules.
			$form = $this->model->getForm($this->data, false);

			// Test whether the data is valid.
			$validData = $this->model->validate($form, $this->data);

			if (!isset($validData['tags']))
			{
				$validData['tags'] = null;
			}

			// Attempt to save the data.
			try
			{
				$this->model->save($validData);
			}
			catch (\Exception $e)
			{
				// Save the data in the session.
				$this->app->setUserState($this->context . '.data', $validData);

				// Redirect back to the edit screen.
				throw new \Exception(\JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $e->getMessage()));
			}

			// Invoke the postSave method to allow for the child class to access the model.
			$this->postSaveHook($this->model, $validData);
		}
		catch (ValidateFailException $e)
		{
			$errors = $e->getErrors();

			foreach ($errors as $error)
			{
				if ($error instanceof \Exception)
				{
					$this->app->enqueueMessage($error->getMessage(), 'warning');
				}
				else
				{
					$this->app->enqueueMessage($error, 'warning');
				}
			}

			// Save the data in the session.
			$this->app->setUserState($this->context . '.data', $data);

			// Redirect back to the edit screen.
			$this->app->redirect(\JRoute::_($this->getRedirectItemUrl($this->recordId, $this->urlVar), false));

			return false;
		}
		catch (\Exception $e)
		{
			$this->app->enqueueMessage($e->getMessage(), 'error');

			$this->app->redirect(\JRoute::_($this->getRedirectItemUrl($this->recordId, $this->urlVar), false));

			return false;
		}

		$this->app->enqueueMessage(
			\JText::_(
				($this->lang->hasKey(strtoupper($this->option) . ($this->recordId == 0 && $this->app->isSite() ? '_SUBMIT' : '') . '_SAVE_SUCCESS')
					? strtoupper($this->option)
					: 'JLIB_APPLICATION') . ($this->recordId == 0 && $this->app->isSite() ? '_SUBMIT' : '') . '_SAVE_SUCCESS'
			)
		);

		return true;
	}

	/**
	 * postExecute
	 *
	 * @param null $return
	 *
	 * @return mixed|null
	 */
	protected function postExecute($return = null)
	{
		$this->input->set('layout', null);

		// Clear the record id and data from the session.
		$this->releaseEditId($this->context, $this->recordId);
		$this->app->setUserState($this->context . '.data', null);

		$this->app->redirect(\JRoute::_($this->getRedirectListUrl(), false));

		return $return;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->key = $key;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUrlVar()
	{
		return $this->urlVar;
	}

	/**
	 * @param string $urlVar
	 */
	public function setUrlVar($urlVar)
	{
		$this->urlVar = $urlVar;

		return $this;
	}

	/**
	 * postSaveHook
	 *
	 * @param $model
	 * @param $validData
	 *
	 * @return void
	 */
	protected function postSaveHook($model, $validData)
	{
	}
}