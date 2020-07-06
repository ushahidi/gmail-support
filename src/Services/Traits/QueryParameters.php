<?php

namespace Ushahidi\Gmail\Services\Traits;

use  Ushahidi\Gmail\Services\Mailbox;

trait QueryParameters
{
	/**
	 * Specify the maximum number of messages to return
	 *
	 * @param  int  $number
	 *
	 * @return self|Mailbox
	 */
	public function take($number)
	{
		$this->params['maxResults'] = (int) $number;

		return $this;
	}

	/**
	 * Set the page token to retrieve a specific page of results in the list.
	 *
	 * @param  string  $token
	 *
	 * @return self|Mailbox
	 */
	public function page($token)
	{
		$this->params['pageToken'] = $token;

		return $this;
	}

	/**
	 * Set the history id.
	 *
	 * @param  string  $historyId
	 *
	 * @return self|Mailbox
	 */
	public function history($historyId)
	{
		$this->params['startHistoryId'] = $historyId;

		return $this;
	}

	/**
	 * Set the history types.
	 *
	 * @param  string  $value
	 *
	 * @return self|Mailbox
	 */
	public function historyTypes($value)
	{
		if (!isset($this->params['historyTypes'])) {
			$this->params['historyTypes'] = '';
		}

		$this->params['historyTypes'] = trim("{$this->params['historyTypes']} $value");

		return $this;
	}

	/**
	 * Set query filters for the request.
	 *
	 * @param  string  $query
	 *
	 * @return self|Mailbox
	 */
	public function filter($query)
	{
		if (!isset($this->params['q'])) {
			$this->params['q'] = '';
		}

		$this->params['q'] = trim("{$this->params['q']} $query");

		return $this;
	}

	/**
	 * Set the list of IDs of labels applied to the query.
	 *
	 * @param  string|array  $label
	 *
	 * @return self|Mailbox
	 */
	public function label($label)
	{
		if (!isset($this->params['labelIds'])) {
			$this->params['labelIds'] = [];
		}

		is_array($label)
			? ($this->params['labelIds'] = array_merge($this->params['labelIds'], $label))
			: array_push($this->params['labelIds'], $label);

		return $this;
	}
}
